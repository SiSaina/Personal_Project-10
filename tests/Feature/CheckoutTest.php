<?php

use App\Mail\OrderConfirmation;
use App\Mail\OrderShippingUpdate;
use App\Models\Address;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Admin', 'Employee', 'Customer'] as $type) {
        Role::create(['role_type' => $type]);
    }
});

function checkoutUser(string $role = 'Customer'): User
{
    return User::factory()->create(['role_id' => Role::where('role_type', $role)->value('id')]);
}

function checkoutAddress(User $user): Address
{
    return Address::factory()->create(['user_id' => $user->id]);
}

function checkoutProduct(string $name, string $price, string $offer = '0.00'): Product
{
    $category = Category::firstOrCreate(['name' => 'Test']);

    return Product::create([
        'category_id' => $category->id,
        'name' => $name,
        'description' => 'Test product',
        'price' => $price,
        'offer_price' => $offer,
        'date' => now()->toDateString(),
    ]);
}

it('checks out atomically with server-side price snapshots and totals', function () {
    Mail::fake();
    $user = checkoutUser();
    $address = checkoutAddress($user);
    $sale = checkoutProduct('Sale item', '19.99', '14.50');
    $regular = checkoutProduct('Regular item', '2.25');
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/orders', [
        'addressId' => $address->id,
        'subtotal' => '0.01',
        'total' => '0.01',
        'items' => [
            ['productId' => $sale->id, 'quantity' => 2, 'unitPrice' => '0.01'],
            ['productId' => $regular->id, 'quantity' => 3],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.subtotal', '35.75')
        ->assertJsonPath('data.total', '35.75')
        ->assertJsonPath('data.items.0.unitPrice', '14.50')
        ->assertJsonPath('data.items.0.lineTotal', '29.00');

    $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'total' => 35.75]);
    $this->assertDatabaseHas('order_items', [
        'product_id' => $sale->id,
        'product_name' => 'Sale item',
        'unit_price' => 14.50,
        'quantity' => 2,
        'line_total' => 29.00,
    ]);
    Mail::assertSent(OrderConfirmation::class, fn ($mail) => $mail->order->user_id === $user->id);
});

it('rejects invalid checkout payloads and addresses owned by another user', function () {
    $user = checkoutUser();
    $otherAddress = checkoutAddress(checkoutUser());
    $product = checkoutProduct('Item', '10.00');
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/orders', [
        'addressId' => $otherAddress->id,
        'items' => [['productId' => $product->id, 'quantity' => 1]],
    ])->assertNotFound();

    $this->postJson('/api/v1/orders', [
        'addressId' => $otherAddress->id,
        'items' => [
            ['productId' => $product->id, 'quantity' => 0],
            ['productId' => $product->id, 'quantity' => 1],
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'items.0.product_id',
        'items.0.quantity',
    ]);

    expect(Order::count())->toBe(0);
});

it('enforces order ownership and staff-only status changes', function () {
    $owner = checkoutUser();
    $other = checkoutUser();
    $order = Order::factory()->create(['user_id' => $owner->id, 'address_id' => checkoutAddress($owner)->id]);

    Sanctum::actingAs($other);
    $this->getJson("/api/v1/orders/{$order->id}")->assertForbidden();
    $this->patchJson("/api/v1/orders/{$order->id}", ['paymentStatus' => 'paid'])->assertForbidden();

    Sanctum::actingAs(checkoutUser('Employee'));
    $this->patchJson("/api/v1/orders/{$order->id}", ['paymentStatus' => 'paid'])
        ->assertSuccessful()->assertJsonPath('data.paymentStatus', 'paid');
});

it('enforces fulfillment order and sends shipping updates', function () {
    Mail::fake();
    $customer = checkoutUser();
    $order = Order::factory()->create(['user_id' => $customer->id, 'address_id' => checkoutAddress($customer)->id]);
    Sanctum::actingAs(checkoutUser('Employee'));

    $this->patchJson("/api/v1/orders/{$order->id}", ['fulfillmentStatus' => 'shipped'])
        ->assertUnprocessable()->assertJsonValidationErrors('fulfillmentStatus');

    foreach (['processing', 'shipped', 'delivered'] as $status) {
        $this->patchJson("/api/v1/orders/{$order->id}", ['fulfillmentStatus' => $status])
            ->assertSuccessful()->assertJsonPath('data.fulfillmentStatus', $status);
    }

    Mail::assertSent(OrderShippingUpdate::class, 3);
    expect($order->fresh()->shipped_at)->not->toBeNull()
        ->and($order->fresh()->delivered_at)->not->toBeNull();
});

it('returns stock once when an unpaid order is cancelled', function () {
    Mail::fake();
    $customer = checkoutUser();
    $address = checkoutAddress($customer);
    $product = checkoutProduct('Reserved item', '12.00');
    $product->update(['stock_quantity' => 5]);
    Sanctum::actingAs($customer);
    $orderId = $this->postJson('/api/v1/orders', [
        'addressId' => $address->id,
        'paymentMethod' => 'bank_transfer',
        'items' => [['productId' => $product->id, 'quantity' => 2]],
    ])->assertCreated()->json('data.id');

    expect($product->fresh()->stock_quantity)->toBe(3);
    Sanctum::actingAs(checkoutUser('Employee'));
    $this->patchJson("/api/v1/orders/{$orderId}", ['fulfillmentStatus' => 'cancelled'])
        ->assertSuccessful()->assertJsonPath('data.status', 'cancelled');
    expect($product->fresh()->stock_quantity)->toBe(5)
        ->and(Order::find($orderId)->inventory_restored_at)->not->toBeNull();

    $this->patchJson("/api/v1/orders/{$orderId}", ['fulfillmentStatus' => 'cancelled'])->assertSuccessful();
    expect($product->fresh()->stock_quantity)->toBe(5);
});

it('applies coupons and decrements inventory atomically', function () {
    $user = checkoutUser();
    $address = checkoutAddress($user);
    $product = checkoutProduct('Limited item', '20.00');
    $product->update(['stock_quantity' => 3]);
    Coupon::create(['code' => 'SAVE25', 'percent_off' => 25, 'active' => true]);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/orders', [
        'addressId' => $address->id,
        'couponCode' => 'SAVE25',
        'paymentMethod' => 'bank_transfer',
        'items' => [['productId' => $product->id, 'quantity' => 2]],
    ])->assertCreated()
        ->assertJsonPath('data.discountTotal', '10.00')
        ->assertJsonPath('data.total', '30.00')
        ->assertJsonPath('data.paymentMethod', 'bank_transfer')
        ->assertJsonPath('data.fulfillmentStatus', 'unfulfilled');

    expect($product->fresh()->stock_quantity)->toBe(1);
});
