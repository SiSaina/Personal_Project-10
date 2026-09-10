<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Admin', 'Employee', 'Customer'] as $type) {
        Role::create(['role_type' => $type]);
    }
});

it('registers customers without accepting role escalation', function () {
    $this->postJson('/api/register', [
        'name' => 'A Customer',
        'email' => 'new@example.com',
        'password' => 'password-123',
        'password_confirmation' => 'password-123',
        'role_id' => Role::where('role_type', 'Admin')->value('id'),
    ])->assertSuccessful();

    $user = User::where('email', 'new@example.com')->firstOrFail();
    expect($user->role->role_type)->toBe('Customer')
        ->and(Hash::check('password-123', $user->password))->toBeTrue();
});

it('validates registration and login credentials', function () {
    $this->postJson('/api/register', [
        'name' => '',
        'email' => 'invalid',
        'password' => 'short',
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);

    $this->postJson('/api/login', [
        'email' => 'missing@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('logs in, returns no password, and revokes the token on logout', function () {
    User::factory()->create([
        'email' => 'customer@example.com',
        'password' => 'password-123',
        'role_id' => Role::where('role_type', 'Customer')->value('id'),
    ]);

    $login = $this->postJson('/api/login', [
        'email' => 'customer@example.com',
        'password' => 'password-123',
    ])->assertSuccessful()->assertJsonMissingPath('user.password');

    $token = $login->json('access_token');
    $this->withToken($token)->postJson('/api/logout')->assertSuccessful();
    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
});

it('returns the authenticated user with camelCase fields', function () {
    $user = User::factory()->create([
        'image_url' => 'storage/avatar.png',
        'role_id' => Role::where('role_type', 'Customer')->value('id'),
    ]);
    \Laravel\Sanctum\Sanctum::actingAs($user);

    $this->getJson('/api/user')
        ->assertSuccessful()
        ->assertJsonPath('data.roleId', $user->role_id)
        ->assertJsonPath('data.roleType', 'Customer')
        ->assertJsonPath('data.imageUrl', 'storage/avatar.png')
        ->assertJsonMissingPath('data.role_id')
        ->assertJsonMissingPath('data.image_url');
});
