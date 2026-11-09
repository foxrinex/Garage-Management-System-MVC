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
    Route::post('/customer/auction-purchase', [CustomerController::class, 'auctionPurchase'])->name('customer.auction.purchase');
    Route::post('/customer/builder-action', [CustomerController::class, 'builderAction'])->name('customer.builder.action');
    Route::post('/customer/car-purchase', [CustomerController::class, 'carPurchase'])->name('customer.car.purchase');
    Route::post('/customer/change-password', [CustomerController::class, 'changePassword'])->name('customer.password.update');
    Route::post('/customer/checkout', [CustomerController::class, 'checkout'])->name('customer.checkout');
    Route::get('/customer/delete-build/{id}', [CustomerController::class, 'deleteBuild'])->name('customer.build.delete');
    Route::post('/customer/edit-review', [CustomerController::class, 'editReview'])->name('customer.review.update');
    Route::post('/customer/place-order', [CustomerController::class, 'placeOrder'])->name('customer.order.place');
    Route::match(['get', 'post'], '/customer/post-review', [CustomerController::class, 'postReview'])->name('customer.review.post');
    Route::post('/customer/profile-update', [CustomerController::class, 'updateProfile'])->name('customer.profile.update');
    Route::get('/customer/cart/reduce/{cartId}', [CustomerController::class, 'reduceQuantity'])->name('customer.cart.reduce');
    Route::get('/customer/cart/remove/{cartId}', [CustomerController::class, 'removeFromCart'])->name('customer.cart.remove');
    Route::post('/customer/repair-add', [CustomerController::class, 'storeRepairRequest'])->name('customer.repair.store');
    Route::get('/customer/repair-cancel/{id}', [CustomerController::class, 'cancelRepairRequest'])->name('customer.repair.cancel');
    Route::get('/customer/repair-decline-parts/{id}', [CustomerController::class, 'declineRepairParts'])->name('customer.repair.decline.parts');
    Route::get('/customer/repair-transaction/{id}', [CustomerController::class, 'repairTransaction'])->name('customer.repair.transaction');
    Route::get('/customer/my-reviews/delete/{id}', [CustomerController::class, 'deleteMyReview'])->name('customer.review.delete.my');
    Route::post('/customer/send-message', [CustomerController::class, 'sendMessage'])->name('customer.message.send');
    Route::post('/customer/submit-warranty', [CustomerController::class, 'submitWarrantyClaim'])->name('customer.warranty.submit');
    Route::get('/customer/wishlist-handle/{id}', [CustomerController::class, 'wishlistHandle'])->name('customer.wishlist.handle');
});




Route::get('/', [HomeController::class, 'index'])->name('home');

