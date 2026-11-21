<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoreController::class, 'index'])->name('store');
Route::get('/products/{id}', [StoreController::class, 'product'])->whereNumber('id');
Route::post('/newsletter', [StoreController::class, 'newsletter'])->middleware('throttle:identity');
Route::get('/verify/{id}', fn (int $id) => view('auth.verify', compact('id')))->whereNumber('id');
Route::post('/verify/{id}', [AuthController::class, 'verify'])->whereNumber('id')->middleware('throttle:factor');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/register', 'auth.register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:identity');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:identity');
    Route::get('/email-code', [AuthController::class, 'emailCodeForm']);
    Route::post('/email-code', [AuthController::class, 'verifyEmailCode'])->middleware('throttle:factor');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
Route::middleware(['auth', 'shop.access'])->group(function () {
    Route::get('/cart', [CartController::class, 'show']);
    Route::match(['post', 'patch', 'delete'], '/cart/{id}', [CartController::class, 'change'])->whereNumber('id');
    Route::get('/checkout', [CartController::class, 'review']);
    Route::post('/checkout/coupon', [CartController::class, 'coupon']);
    Route::post('/checkout/pay', [CartController::class, 'pay']);
    Route::get('/orders/{id}', [CartController::class, 'receipt'])->whereNumber('id');
    Route::get('/account', [AccountController::class, 'show']);
    Route::patch('/account', [AccountController::class, 'update'])->middleware('throttle:identity');
});
Route::prefix('admin')->middleware(['auth', 'shop.access:admin'])->group(function () {
    Route::get('/{section?}', [AdminController::class, 'index'])->whereIn('section', ['products', 'users', 'coupons']);
    Route::patch('/users/{id}', [AdminController::class, 'user'])->whereNumber('id');
    Route::post('/products', [AdminController::class, 'product']);
    Route::patch('/products/{id}', [AdminController::class, 'product'])->whereNumber('id');
    Route::post('/coupons', [AdminController::class, 'coupon']);
    Route::patch('/coupons/{id}',[AdminController::class, 'coupon'])->whereNumber('id');
});
