<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CustomerController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'signup'])->name('signup.post');


Route::post('/contact', [HomeController::class, 'sendContact'])->name('contact.send');



Route::middleware(['auth'])->group(function () {
    Route::post('/customer/account-delete', [CustomerController::class, 'deleteAccount'])->name('customer.account.delete');
    Route::post('/customer/add-to-cart', [CustomerController::class, 'addToCart'])->name('customer.cart.add');
    Route::post('/customer/auction/bid', [CustomerController::class, 'placeBid'])->name('customer.auction.bid');
});




Route::get('/', [HomeController::class, 'index'])->name('home');

