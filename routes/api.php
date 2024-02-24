<?php

use App\Http\Controllers\ProductControler;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendors')
    ->group(function () {
        Route::post('register', [VendorController::class, 'register']);
        Route::post('login', [VendorController::class, 'login']);

        Route::middleware('auth:sanctum', 'vendor')
            ->group(function () {
                Route::get('profile', [VendorController::class, 'profile']);
                Route::post('add-product', [VendorController::class, 'addProduct']);
                Route::get('my-products', [VendorController::class, 'myProducts']);
            });
    });

Route::prefix('users')
    ->group(function () {
        Route::post('register', [UserController::class, 'register']);
        Route::post('login', [UserController::class, 'login']);

        Route::middleware('auth:sanctum', 'user')
            ->group(function () {
                Route::get('profile', [UserController::class, 'profile']);
                Route::post('add-money', [UserController::class, 'addMoney']);
                Route::post('add-to-cart', [UserController::class, 'addToCart']);
                Route::get('my-cart', [UserController::class, 'myCart']);
                Route::post('checkout', [UserController::class, 'checkout']);
                Route::get('my-transactions', [UserController::class, 'transactions']);
                Route::post('give-up', [UserController::class, 'giveUp']);
                Route::post('send-money', [UserController::class, 'sendMoney']);
            });
    });

Route::prefix('products')
    ->group(function() {
        Route::get('list', [ProductControler::class, 'findAll']);
    });
