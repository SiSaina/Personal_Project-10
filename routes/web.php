<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminController::class, 'loginForm'])->name('login');
    Route::post('login', [AdminController::class, 'login'])->middleware('throttle:6,1');
    Route::middleware('admin.session')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::post('logout', [AdminController::class, 'logout'])->name('logout');
        Route::get('products', [AdminController::class, 'products'])->name('products');
        Route::get('products/create', [AdminController::class, 'productForm'])->name('products.create');
        Route::post('products', [AdminController::class, 'saveProduct'])->name('products.store');
        Route::get('products/{product}/edit', [AdminController::class, 'productForm'])->name('products.edit');
        Route::put('products/{product}', [AdminController::class, 'saveProduct'])->name('products.update');
        Route::get('categories', [AdminController::class, 'categories'])->name('categories');
        Route::post('categories', [AdminController::class, 'saveCategory'])->name('categories.store');
        Route::put('categories/{category}', [AdminController::class, 'saveCategory'])->name('categories.update');
        Route::get('users', [AdminController::class, 'users'])->name('users');
        Route::get('users/create', [AdminController::class, 'userForm'])->name('users.create');
        Route::post('users', [AdminController::class, 'saveUser'])->name('users.store');
        Route::get('users/{user}/edit', [AdminController::class, 'userForm'])->name('users.edit');
        Route::put('users/{user}', [AdminController::class, 'saveUser'])->name('users.update');
        Route::get('roles', [AdminController::class, 'roles'])->name('roles');
        Route::post('roles', [AdminController::class, 'ensureRoles'])->name('roles.ensure');
    });
});
