<?php

use App\Models\Address;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Admin', 'Employee', 'Customer'] as $roleType) {
        Role::create(['role_type' => $roleType]);
    }
});

it('ignores a requested privileged role during public registration', function () {
    $admin = Role::where('role_type', 'Admin')->firstOrFail();

    $this->postJson('/api/register', [
        'name' => 'New Customer',
        'email' => 'customer@example.com',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
        'role_id' => $admin->id,
    ])->assertSuccessful();

    expect(User::where('email', 'customer@example.com')->firstOrFail()->role->role_type)
        ->toBe('Customer');
});

it('does not expose password hashes in user resources', function () {
    $admin = User::factory()->create(['role_id' => Role::where('role_type', 'Admin')->value('id')]);
    $customer = User::factory()->create(['role_id' => Role::where('role_type', 'Customer')->value('id')]);
    Sanctum::actingAs($admin);

    $this->getJson("/api/v1/users/{$customer->id}")
        ->assertSuccessful()
        ->assertJsonMissingPath('data.password');
});

it('blocks unauthenticated writes and customer role administration', function () {
    $this->postJson('/api/v1/products', [])->assertUnauthorized();
    $this->postJson('/api/v1/images/bulk', [])->assertUnauthorized();

    $customer = User::factory()->create(['role_id' => Role::where('role_type', 'Customer')->value('id')]);
    Sanctum::actingAs($customer);

    $this->postJson('/api/v1/roles', ['roleType' => 'Admin'])->assertForbidden();
    $this->patchJson("/api/v1/users/{$customer->id}", ['roleId' => Role::where('role_type', 'Admin')->value('id')])
        ->assertForbidden();
});

it('limits customers to their own addresses', function () {
    $customerRole = Role::where('role_type', 'Customer')->value('id');
    $owner = User::factory()->create(['role_id' => $customerRole]);
    $other = User::factory()->create(['role_id' => $customerRole]);
    $address = Address::create([
        'user_id' => $other->id,
        'full_name' => 'Other User',
        'postal_code' => '1010',
        'street_name' => '1 Queen Street',
        'suburb' => 'Central',
        'city' => 'Auckland',
        'country' => 'New Zealand',
    ]);
    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/addresses/{$address->id}")->assertForbidden();
    $this->patchJson("/api/v1/addresses/{$address->id}", ['city' => 'Auckland'])->assertForbidden();
    $this->deleteJson("/api/v1/addresses/{$address->id}")->assertForbidden();

    $this->getJson('/api/v1/addresses')
        ->assertSuccessful()
        ->assertJsonMissing(['id' => $address->id]);
});
