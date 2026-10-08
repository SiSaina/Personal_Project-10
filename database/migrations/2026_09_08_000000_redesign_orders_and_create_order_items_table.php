<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('order_details', 'legacy_order_details');
        Schema::rename('orders', 'legacy_orders');

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamp('placed_at');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
        });

        DB::table('legacy_order_details as details')
            ->join('legacy_orders as legacy', 'legacy.id', '=', 'details.order_id')
            ->join('products', 'products.id', '=', 'legacy.product_id')
            ->select([
                'details.user_id',
                'details.address_id',
                'details.status',
                'details.date',
                'legacy.product_id',
                'legacy.quantity',
                'products.name as product_name',
                'products.price',
                'products.offer_price',
            ])
            ->orderBy('details.id')
            ->chunk(100, function ($legacyRows) {
                foreach ($legacyRows as $legacy) {
                    $regularPrice = (float) $legacy->price;
                    $offerPrice = (float) $legacy->offer_price;
                    $unitPrice = $offerPrice > 0 && $offerPrice < $regularPrice
                        ? $offerPrice
                        : $regularPrice;
                    $lineTotal = round($unitPrice * $legacy->quantity, 2);
                    $timestamp = now();

                    $orderId = DB::table('orders')->insertGetId([
                        'user_id' => $legacy->user_id,
                        'address_id' => $legacy->address_id,
                        'status' => $legacy->status === 'canceled' ? 'cancelled' : $legacy->status,
                        'subtotal' => $lineTotal,
                        'total' => $lineTotal,
                        'placed_at' => $legacy->date.' 00:00:00',
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);

                    DB::table('order_items')->insert([
                        'order_id' => $orderId,
                        'product_id' => $legacy->product_id,
                        'product_name' => $legacy->product_name,
                        'unit_price' => $unitPrice,
                        'quantity' => $legacy->quantity,
                        'line_total' => $lineTotal,
                    ]);
                }
            });

        Schema::drop('legacy_order_details');
        Schema::drop('legacy_orders');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity');
        });

        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->date('date');
        });
    }
};
