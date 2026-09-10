<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->default(100);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_method')->default('manual');
            $table->string('fulfillment_status')->default('unfulfilled');
            $table->string('coupon_code')->nullable();
            $table->decimal('discount_total', 12, 2)->default(0);
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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('coupons');
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['payment_status', 'payment_method', 'fulfillment_status', 'coupon_code', 'discount_total']));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('stock_quantity'));
    }
};
