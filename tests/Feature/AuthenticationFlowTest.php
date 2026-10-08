<?php

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::create(['role_type' => 'Admin']);
    $this->customerRole = Role::create(['role_type' => 'Customer']);
});

it('registers customers without accepting a caller chosen privileged role', function () {
    $this->postJson('/api/register', [
        'name' => 'Example Customer',
        'email' => 'customer@example.test',
        'password' => 'test-password',
        'password_confirmation' => 'test-password',
        'role_id' => $this->adminRole->id,
    ])->assertOk()->assertJson(['message' => 'User registered successfully']);

    $user = User::where('email', 'customer@example.test')->firstOrFail();
    expect($user->role_id)->toBe($this->customerRole->id);

    $response = $this->postJson('/api/login', [
        'email' => 'customer@example.test',
        'password' => 'test-password',
    ])->assertOk()->assertJsonPath('user.role.role_type', 'Customer');

    expect($response->json('access_token'))->toBeString()->not->toBeEmpty();
    expect($response->json('user'))->not->toHaveKey('password');
});

it('returns a clear validation error when no Customer role is configured', function () {
    $this->customerRole->delete();

    $this->postJson('/api/register', [
        'name' => 'Example Customer',
        'email' => 'customer@example.test',
        'password' => 'test-password',
        'password_confirmation' => 'test-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->assertDatabaseMissing('users', ['email' => 'customer@example.test']);
});

it('keeps existing sessions valid when another login occurs', function () {
    $user = User::create([
        'name' => 'Example Customer',
        'email' => 'customer@example.test',
        'password' => 'test-password',
        'role_id' => $this->customerRole->id,
    ]);
    $existingToken = $user->createToken('existing-session');

    $this->postJson('/api/login', [
        'email' => 'customer@example.test',
        'password' => 'test-password',
    ])->assertOk();

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $existingToken->accessToken->id]);
    expect($user->tokens()->count())->toBe(2);
});

it('includes the actual role and addresses in the restored profile', function () {
    $user = User::create([
        'name' => 'Example Customer',
        'email' => 'customer@example.test',
        'password' => 'test-password',
        'role_id' => $this->customerRole->id,
    ]);
    $token = $user->createToken('test-session')->plainTextToken;

    $this->withToken($token)->getJson('/api/user?includeAddresses=true')
        ->assertOk()
        ->assertJsonPath('role.role_type', 'Customer')
        ->assertJsonPath('addresses', [])
        ->assertJsonMissingPath('password');
});

it('revokes only the current token on logout', function () {
    $user = User::create([
        'name' => 'Example Customer',
        'email' => 'customer@example.test',
        'password' => 'test-password',
        'role_id' => $this->customerRole->id,
    ]);
    $current = $user->createToken('current-session');
    $other = $user->createToken('other-session');

    $this->withToken($current->plainTextToken)->postJson('/api/logout')->assertOk();
    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $current->accessToken->id]);
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $other->accessToken->id]);
});

it('rejects anonymous protected reads instead of returning order data', function () {
    foreach (['orders', 'orderDetails', 'categories'] as $resource) {
        $this->getJson('/api/v1/'.$resource)->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
    }
});

it('allows protected list reads with a valid customer bearer token', function () {
    $user = User::create([
        'name' => 'Example Customer',
        'email' => 'customer@example.test',
        'password' => 'test-password',
        'role_id' => $this->customerRole->id,
    ]);
    $token = $user->createToken('test-session')->plainTextToken;

    foreach (['orders', 'orderDetails', 'categories'] as $resource) {
        $this->withToken($token)->getJson('/api/v1/'.$resource)
            ->assertOk()->assertJsonPath('data', []);
    }
});

it('accepts recognized stored role names regardless of casing', function (string $storedRole, string $canonicalRole) {
    $role = Role::create(['role_type' => $storedRole]);
    $user = User::create([
        'name' => 'Example User',
        'email' => 'user@example.test',
        'password' => 'test-password',
        'role_id' => $role->id,
    ]);
    $token = $user->createToken('test-session')->plainTextToken;

    foreach (['orders', 'orderDetails', 'categories'] as $resource) {
        $this->withToken($token)->getJson('/api/v1/'.$resource)
            ->assertOk()->assertJsonPath('data', []);
    }

    $this->withToken($token)->getJson('/api/user')
        ->assertOk()->assertJsonPath('role.role_type', $canonicalRole);
    $this->assertDatabaseHas('roles', ['id' => $role->id, 'role_type' => $storedRole]);
})->with([
    ['admin', 'Admin'],
    ['employee', 'Employee'],
    ['customer', 'Customer'],
    [' ADMIN ', 'Admin'],
]);

it('registers customers using an existing lowercase customer role', function () {
    $this->customerRole->update(['role_type' => 'customer']);

    $this->postJson('/api/register', [
        'name' => 'Example Customer',
        'email' => 'customer@example.test',
        'password' => 'test-password',
        'password_confirmation' => 'test-password',
        'role_id' => $this->adminRole->id,
    ])->assertOk();

    expect(User::where('email', 'customer@example.test')->firstOrFail()->role_id)
        ->toBe($this->customerRole->id);
});

it('still denies admin-only actions to lowercase non-admin roles', function (string $roleName) {
    $category = Category::create(['name' => 'Protected category']);
    $role = Role::create(['role_type' => $roleName]);
    $user = User::create([
        'name' => 'Example User',
        'email' => 'user@example.test',
        'password' => 'test-password',
        'role_id' => $role->id,
    ]);
    $token = $user->createToken('test-session')->plainTextToken;

    $this->withToken($token)->deleteJson('/api/v1/categories/'.$category->id)
        ->assertForbidden()->assertJson(['message' => 'You do not have permission to access this resource.']);
    $this->assertDatabaseHas('categories', ['id' => $category->id]);
})->with(['customer', 'employee']);

it('does not grant access for missing or unrecognized roles', function (?string $roleName) {
    $role = $roleName === null ? null : Role::create(['role_type' => $roleName]);
    $user = User::create([
        'name' => 'Example User',
        'email' => 'user@example.test',
        'password' => 'test-password',
        'role_id' => $role?->id,
    ]);
    $token = $user->createToken('test-session')->plainTextToken;

    foreach (['orders', 'orderDetails', 'categories'] as $resource) {
        $this->withToken($token)->getJson('/api/v1/'.$resource)->assertForbidden();
    }
})->with([null, 'administrator']);
