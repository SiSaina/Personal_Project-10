<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function panelUser(string $role): User
{
    return User::create(['name' => $role, 'email' => strtolower($role).'@example.test',
        'password' => 'test-password', 'role_id' => Role::firstOrCreate(['role_type' => $role])->id]);
}

it('redirects guests and rejects employees and customers on admin pages', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
    foreach (['Employee', 'Customer'] as $role) {
        $this->actingAs(panelUser($role), 'web')->get('/admin')->assertForbidden();
        $this->post('/admin/products', [])->assertForbidden();
    }
});

it('logs an administrator in and out and renders the panel', function () {
    $admin = panelUser('Admin');
    $this->get('/admin/login')->assertOk()->assertSee('Admin login');
    $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    $this->post('/admin/login', ['email' => $admin->email, 'password' => 'test-password'])->assertRedirect('/admin');
    $this->assertAuthenticatedAs($admin, 'web');
    $this->get('/admin')->assertOk()->assertSee('Dashboard');
    $this->get('/admin/products')->assertOk()->assertSee('No products found');
    $this->get('/admin/categories')->assertOk();
    $this->get('/admin/products/create')->assertOk()->assertSee('Create a');
    $this->post('/admin/logout')->assertRedirect('/admin/login');
    $this->assertGuest('web');
});

it('rejects non-admin credentials without creating an admin session', function () {
    $user = panelUser('Customer');
    $this->post('/admin/login', ['email' => $user->email, 'password' => 'test-password'])->assertSessionHasErrors('email');
    $this->assertGuest('web');
});

it('creates and edits categories and products with validation and search', function () {
    $this->actingAs(panelUser('Admin'), 'web');
    $this->post('/admin/categories', ['name' => 'Electronics'])->assertRedirect('/admin/categories');
    $category = Category::firstOrFail();
    $this->put('/admin/categories/'.$category->id, ['name' => 'Cameras'])->assertRedirect('/admin/categories');
    $payload = ['name' => 'Camera', 'category_id' => $category->id, 'description' => 'Demo camera',
        'price' => 120, 'offer_price' => 100, 'date' => '2026-10-08'];
    $this->post('/admin/products', $payload)->assertRedirect('/admin/products');
    $product = Product::firstOrFail();
    $this->get('/admin/products/'.$product->id.'/edit')->assertOk()->assertSee('Demo camera');
    $this->put('/admin/products/'.$product->id, [...$payload, 'price' => 150])->assertRedirect('/admin/products');
    $this->assertDatabaseHas('products', ['id' => $product->id, 'price' => 150]);
    $this->get('/admin/products?search=Camera')->assertOk()->assertSee('Camera');
    $this->get('/admin/products?search=missing')->assertOk()->assertSee('No products found');
    $this->post('/admin/products', [...$payload, 'name' => 'Invalid', 'offer_price' => 200])->assertSessionHasErrors('offer_price');
    $this->assertDatabaseCount('products', 1);
});

it('blocks staff API mutations and role escalation for customers', function () {
    $customer = panelUser('Customer');
    $this->getJson('/api/v1/products')->assertOk();
    $this->postJson('/api/v1/products', [])->assertUnauthorized();
    $this->withToken($customer->createToken('test')->plainTextToken);
    foreach (['products', 'categories', 'images/bulk', 'users', 'roles'] as $endpoint) {
        $this->postJson('/api/v1/'.$endpoint, [])->assertForbidden();
    }
});

it('manages users and supported roles while preserving blank passwords', function () {
    $admin = panelUser('Admin');
    $this->actingAs($admin, 'web');
    $this->post('/admin/roles')->assertRedirect('/admin/roles');
    $this->post('/admin/roles')->assertRedirect('/admin/roles');
    $this->assertDatabaseCount('roles', 3);
    $this->get('/admin/roles')->assertOk()->assertSee('Admin');
    $role = Role::where('role_type', 'Employee')->firstOrFail();
    $data = ['name' => 'Lina', 'email' => 'lina@example.test', 'role_id' => $role->id,
        'password' => 'test-password', 'password_confirmation' => 'test-password'];
    $this->get('/admin/users/create')->assertOk();
    $this->post('/admin/users', $data)->assertRedirect('/admin/users');
    $user = User::where('email', $data['email'])->firstOrFail();
    $hash = $user->password;
    $tokenId = $user->createToken('old')->accessToken->id;
    $this->get('/admin/users/'.$user->id.'/edit')->assertOk()->assertDontSee($hash);
    $this->put('/admin/users/'.$user->id, [...$data, 'name' => 'Lina Updated', 'password' => '', 'password_confirmation' => ''])->assertRedirect('/admin/users');
    expect($user->fresh()->password)->toBe($hash);
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenId]);
    $this->put('/admin/users/'.$user->id, [...$data, 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertRedirect('/admin/users');
    expect(\Illuminate\Support\Facades\Hash::check('new-password', $user->fresh()->password))->toBeTrue();
    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    $this->get('/admin/users?search=Lina&role='.$role->id)->assertOk()->assertSee('lina@example.test');
    $this->post('/admin/users', $data)->assertSessionHasErrors('email');
    $this->put('/admin/users/'.$admin->id, ['name' => $admin->name, 'email' => $admin->email, 'role_id' => $role->id])->assertSessionHasErrors('role_id');
    expect($admin->fresh()->role_id)->toBe($admin->role_id);
});

it('denies customer access to user and role management', function () {
    $user = panelUser('Customer');
    $this->actingAs($user, 'web');
    foreach (['users', 'users/create', 'users/'.$user->id.'/edit', 'roles'] as $path) {
        $this->get('/admin/'.$path)->assertForbidden();
    }
    $this->post('/admin/users', [])->assertForbidden();
    $this->put('/admin/users/'.$user->id, [])->assertForbidden();
    $this->post('/admin/roles')->assertForbidden();
});
