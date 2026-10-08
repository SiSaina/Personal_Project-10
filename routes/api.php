<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ImageController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderDetailController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user'])->name('user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::prefix('v1')->namespace('App\Http\Controllers\Api\V1')->group(function () {
    Route::post('images/bulk', [ImageController::class, 'bulkStore'])->middleware(['auth:sanctum', 'role:Admin,Employee']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('addresses', AddressController::class);
    });
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    Route::middleware(['auth:sanctum', 'role:Admin,Employee'])->group(function () {
        Route::apiResource('products', ProductController::class)->only(['store', 'update']);
        Route::apiResource('categories', CategoryController::class)->only(['store', 'update']);
        Route::apiResource('images', ImageController::class)->only(['store', 'update']);
    });
    Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
        Route::apiResource('users', UserController::class)->only(['index', 'show', 'store', 'update']);
        Route::apiResource('roles', RoleController::class)->only(['index', 'show', 'store', 'update']);
    });
    // Read Permissions
    Route::middleware(['auth:sanctum', 'role:Admin,Employee,Customer'])->group(function () {
        Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
        Route::apiResource('images', ImageController::class)->only(['index', 'show']);

        Route::apiResource('orderDetails', OrderDetailController::class)->only(['index', 'show', 'store', 'update']);
        Route::get('orderDetails/userProduct/{id}', [OrderDetailController::class, 'showUserProduct']);


        Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store', 'update']);
        Route::post('orders/bulk', [OrderController::class, 'bulkStore']);
    });

    
    // // Create/Update Permissions
    // Route::middleware(['auth:sanctum', 'role:Admin,Employee'])->group(function () {
    //     Route::apiResource('categories', CategoryController::class)->only(['store', 'update']);
    //     Route::apiResource('images', ImageController::class)->only(['store', 'update']);
    //     Route::apiResource('orders', OrderController::class)->only(['store', 'update']);
    //     Route::apiResource('orderDetails', OrderDetailController::class)->only(['store', 'update']);
    //     Route::apiResource('products', ProductController::class)->only(['store', 'update']);
    //     Route::apiResource('users', UserController::class)->only(['store', 'update']);
    //     Route::apiResource('roles', RoleController::class)->only(['store', 'update']);
    // });

    // Full Permissions (admin)
    Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
        Route::apiResource('categories', CategoryController::class)->only(['destroy']);
        Route::apiResource('images', ImageController::class)->only(['destroy']);
        Route::apiResource('orders', OrderController::class)->only(['destroy']);
        Route::apiResource('orderDetails', OrderDetailController::class)->only(['destroy']);
        Route::apiResource('products', ProductController::class)->only(['destroy']);
        Route::apiResource('users', UserController::class)->only(['destroy']);
        Route::apiResource('roles', RoleController::class)->only(['destroy']);
    });
});
