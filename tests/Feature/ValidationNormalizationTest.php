<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('accepts camelCase input without nulling omitted patch fields', function () {
    $employeeRole = Role::create(['role_type' => 'Employee']);
    $employee = User::factory()->create(['role_id' => $employeeRole->id]);
    $category = Category::create(['name' => 'Original']);
    $otherCategory = Category::create(['name' => 'Updated']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Product',
        'description' => 'Description',
        'price' => '20.00',
        'offer_price' => '18.00',
        'date' => now()->toDateString(),
    ]);
    Sanctum::actingAs($employee);

    $this->patchJson("/api/v1/products/{$product->id}", [
        'categoryId' => $otherCategory->id,
        'offerPrice' => 12.50,
    ])->assertSuccessful()
        ->assertJsonPath('data.offerPrice', '12.50');

    $product->refresh();
    expect($product->category_id)->toBe($otherCategory->id)
        ->and($product->name)->toBe('Product')
        ->and((string) $product->offer_price)->toBe('12.50');
});
