<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Independent in-memory SQLite connection, never the local application DB.
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    DB::purge('sqlite');
    foreach (glob(database_path('migrations/*.php')) as $path) {
        if (! str_contains(basename($path), '2026_10_09')) {
            (require $path)->up();
        }
    }
    DB::table('categories')->insert(['id' => 1, 'name' => 'Test']);
    DB::table('users')->insert(['id' => 1, 'name' => 'Buyer', 'email' => 'buyer@example.test', 'password' => 'unused']);
    DB::table('products')->insert(['id' => 1, 'category_id' => 1, 'name' => 'Camera',
        'description' => 'Test', 'price' => 20, 'offer_price' => 15, 'date' => '2026-10-09']);
    DB::table('orders')->insert([
        ['id' => 41, 'product_id' => 1, 'quantity' => 2],
        ['id' => 42, 'product_id' => 1, 'quantity' => 1],
    ]);
    DB::table('order_details')->insert(['order_id' => 41, 'user_id' => 1, 'status' => 'pending', 'date' => '2026-10-09']);
});

afterEach(function () {
    DB::purge('sqlite');
});

it('preserves old rows and IDs and permits new multi item orders', function () {
    $migration = require database_path('migrations/2026_10_09_000000_extend_orders_without_recreating_tables.php');
    $migration->up();
    expect(DB::table('orders')->count())->toBe(2);
    expect(DB::table('order_details')->count())->toBe(1);
    expect(DB::table('order_items')->count())->toBe(2);
    $order = DB::table('orders')->where('id', 41)->first();
    expect($order->user_id)->toBe(1);
    expect((float) $order->total)->toBe(30.0);
    expect(DB::table('orders')->where('id', 42)->value('user_id'))->toBeNull();
    expect(DB::table('order_items')->where('order_id', 41)->value('product_name'))->toBe('Camera');
    $id = DB::table('orders')->insertGetId(['user_id' => 1, 'subtotal' => 15, 'total' => 15, 'placed_at' => now()]);
    expect($id)->toBeGreaterThan(42);
    expect(DB::table('orders')->where('id', $id)->value('product_id'))->toBeNull();
    foreach (['coupons', 'reviews', 'wishlists'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
    expect(DB::table('orders')->count())->toBe(3);
});

it('splits multiple details without losing records or processing new orders twice', function () {
    DB::table('users')->insert(['id' => 2, 'name' => 'Other buyer', 'email' => 'other@example.test', 'password' => 'unused']);
    $detailId = DB::table('order_details')->insertGetId(['order_id' => 41, 'user_id' => 2, 'status' => 'pending', 'date' => '2026-02-03']);
    $migration = require database_path('migrations/2026_10_09_000000_extend_orders_without_recreating_tables.php');
    $migration->up();
    expect(DB::table('orders')->count())->toBe(3);
    expect(DB::table('order_details')->count())->toBe(2);
    expect(DB::table('order_items')->count())->toBe(3);
    expect(DB::table('orders')->where('id', 41)->value('user_id'))->toBe(1);
    $newId = DB::table('order_details')->where('id', $detailId)->value('order_id');
    expect($newId)->toBeGreaterThan(42);
    $split = DB::table('orders')->where('id', $newId)->first();
    expect($split->user_id)->toBe(2);
    expect($split->placed_at)->toBe('2026-02-03 00:00:00');
    expect($split->product_id)->toBe(1);
    expect($split->quantity)->toBe(2);
    expect((float) $split->total)->toBe(30.0);
    expect(DB::table('order_items')->where('order_id', $newId)->count())->toBe(1);
});
