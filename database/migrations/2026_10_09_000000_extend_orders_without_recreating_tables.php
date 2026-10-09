<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the original orders table, IDs, rows and legacy columns.
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->integer('quantity')->nullable()->change();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('address_id')->nullable()->constrained('addresses');
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_method')->default('manual');
            $table->string('fulfillment_status')->default('unfulfilled');
            $table->string('coupon_code')->nullable();
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->timestamp('inventory_restored_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->string('product_name');
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
        });

        DB::transaction(function () {
            // Bound the scan so newly split orders are not migrated a second time.
            $lastOriginalId = DB::table('orders')->max('id') ?? 0;
            DB::table('orders')->where('id', '<=', $lastOriginalId)->chunkById(100, function ($orders) {
                foreach ($orders as $order) {
                    $details = DB::table('order_details')->where('order_id', $order->id)->orderBy('id')->get();
                    $product = DB::table('products')->where('id', $order->product_id)->first();
                    $price = $product ? (float) $product->price : 0;
                    $offer = $product ? (float) $product->offer_price : 0;
                    $price = $offer > 0 && $offer < $price ? $offer : $price;
                    $lineTotal = round($price * $order->quantity, 2);
                    // The lowest detail ID keeps the original order. Others get
                    // separate orders without losing their buyer or date.
                    foreach ($details->isEmpty() ? [null] : $details as $index => $detail) {
                        $values = ['subtotal' => $lineTotal, 'total' => $lineTotal];
                        if ($detail) {
                        $values += [
                            'user_id' => $detail->user_id,
                            'address_id' => $detail->address_id,
                            'status' => $detail->status === 'canceled' ? 'cancelled' : $detail->status,
                            'placed_at' => $detail->date.' 00:00:00',
                        ];
                        }
                        $orderId = $order->id;
                        if ($index === 0) {
                            DB::table('orders')->where('id', $orderId)->update($values);
                        } else {
                            $orderId = DB::table('orders')->insertGetId($values + [
                                'product_id' => $order->product_id,
                                'quantity' => $order->quantity,
                            ]);
                            DB::table('order_details')->where('id', $detail->id)->update(['order_id' => $orderId]);
                        }
                    DB::table('order_items')->insert([
                        'order_id' => $orderId,
                        'product_id' => $product?->id,
                        'product_name' => $product?->name ?? 'Legacy product unavailable',
                        'unit_price' => $price,
                        'quantity' => $order->quantity,
                        'line_total' => $lineTotal,
                    ]);
                    }
                }
            });
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->default(100);
        });
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedTinyInteger('percent_off');
            $table->boolean('active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('product_id')->constrained('products');
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        // A rollback cannot safely fit multi-item orders into the old schema.
        throw new RuntimeException('This data-preserving migration cannot be rolled back automatically. Restore a database backup if you need the previous schema.');
    }
};
