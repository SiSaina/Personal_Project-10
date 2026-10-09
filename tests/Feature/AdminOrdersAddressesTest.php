<?php

use App\Models\Address;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function orderPanelAccount(string $role): User
{
    return User::create(['name' => $role, 'email' => strtolower($role).'@panel.test',
        'password' => 'test-password', 'role_id' => Role::firstOrCreate(['role_type' => $role])->id]);
}

function orderPanelAddress(User $user): array
{
    return ['user_id' => $user->id, 'full_name' => 'Rita Example', 'postal_code' => '1010',
        'street_name' => '10 Example Road', 'suburb' => 'Central', 'city' => 'Auckland', 'country' => 'New Zealand'];
}

it('protects the new order and address pages from guests and non admins', function () {
    $owner = orderPanelAccount('Admin');
    Address::create(orderPanelAddress($owner));
    Order::create(['user_id' => $owner->id, 'subtotal' => 0, 'total' => 0, 'placed_at' => now()]);
    foreach (['orders', 'orders/1', 'addresses', 'addresses/create', 'addresses/1/edit'] as $path) {
        $this->get('/admin/'.$path)->assertRedirect('/admin/login');
    }
    foreach (['Employee', 'Customer'] as $role) {
        $this->actingAs(orderPanelAccount($role), 'web');
        foreach (['orders', 'orders/1', 'addresses', 'addresses/create', 'addresses/1/edit'] as $path) {
            $this->get('/admin/'.$path)->assertForbidden();
        }
        $this->post('/admin/addresses', [])->assertForbidden();
        $this->put('/admin/addresses/1', [])->assertForbidden();
    }
});

it('lists searches and shows saved order items even without a product or address', function () {
    $this->actingAs(orderPanelAccount('Admin'), 'web');
    $buyer = orderPanelAccount('Customer');
    $order = Order::create(['user_id' => $buyer->id, 'status' => 'pending', 'subtotal' => 40,
        'total' => 36, 'discount_total' => 4, 'coupon_code' => 'WELCOME10', 'placed_at' => now()]);
    $order->items()->create(['product_id' => null, 'product_name' => 'Saved camera name',
        'unit_price' => 20, 'quantity' => 2, 'line_total' => 40]);
    $this->get('/admin/orders')->assertOk()->assertSee($buyer->email)->assertSee('View details');
    $this->get('/admin/orders?search='.$order->id)->assertOk()->assertSee($buyer->email);
    $this->get('/admin/orders?search=customer')->assertOk()->assertSee($buyer->email);
    $this->get('/admin/orders?search=missing')->assertOk()->assertSee('No orders found');
    $this->get('/admin/orders/'.$order->id)->assertOk()->assertSee('Saved camera name')
        ->assertSee('36.00')->assertSee('WELCOME10')->assertSee('Address unavailable')->assertSee('Product unavailable');
    $this->get('/admin/orders/9999')->assertNotFound();
});

it('creates edits and searches addresses and validates the owner and fields', function () {
    $this->actingAs(orderPanelAccount('Admin'), 'web');
    $buyer = orderPanelAccount('Customer');
    $data = orderPanelAddress($buyer);
    $this->get('/admin/addresses')->assertOk()->assertSee('No addresses found');
    $this->get('/admin/addresses/create')->assertOk()->assertSee($buyer->email);
    $this->post('/admin/addresses', $data)->assertRedirect('/admin/addresses');
    $address = Address::firstOrFail();
    $this->get('/admin/addresses/'.$address->id.'/edit')->assertOk()->assertSee('10 Example Road');
    $this->put('/admin/addresses/'.$address->id, [...$data, 'city' => 'Wellington'])->assertRedirect('/admin/addresses');
    $this->assertDatabaseHas('addresses', ['id' => $address->id, 'city' => 'Wellington']);
    $this->get('/admin/addresses?search=Wellington')->assertOk()->assertSee('Rita Example');
    $this->get('/admin/addresses?search='.$buyer->email)->assertOk()->assertSee('Rita Example');
    $this->get('/admin/addresses?search=missing')->assertOk()->assertSee('No addresses found');
    $this->post('/admin/addresses', [...$data, 'user_id' => 9999])->assertSessionHasErrors('user_id');
    $this->post('/admin/addresses', [...$data, 'postal_code' => str_repeat('1', 11)])->assertSessionHasErrors('postal_code');
    $this->post('/admin/addresses', [...$data, 'full_name' => ''])->assertSessionHasErrors('full_name');
    $this->assertDatabaseCount('addresses', 1);
});

it('shows the linked delivery address and prevents changing its owner', function () {
    $admin = orderPanelAccount('Admin');
    $this->actingAs($admin, 'web');
    $buyer = orderPanelAccount('Customer');
    $data = orderPanelAddress($buyer);
    $address = Address::create($data);
    $order = Order::create(['user_id' => $buyer->id, 'address_id' => $address->id,
        'subtotal' => 0, 'total' => 0, 'placed_at' => now()]);
    $this->get('/admin/orders/'.$order->id)->assertOk()->assertSee('10 Example Road')->assertSee('No order items found');
    $this->put('/admin/addresses/'.$address->id, [...$data, 'user_id' => $admin->id])->assertSessionHasErrors('user_id');
    expect($address->fresh()->user_id)->toBe($buyer->id);
});
