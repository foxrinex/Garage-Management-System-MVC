<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ManagerController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make good something great!
|
*/

// ==========================================
// PUBLIC LANDING & AUTHENTICATION ROUTES
// ==========================================

Route::get('/', function () {
    return view('welcome');
});


// Login Page Routes
Route::get('/login', function () {
    return view('login'); // maps to resources/views/login.blade.php
})->name('login');

Route::post('/login', [AuthController::class, 'login']);


// Signup Page Routes
Route::get('/signup', function () {
    return view('signup'); // maps to resources/views/signup.blade.php
})->name('signup');

Route::post('/signup', [AuthController::class, 'signup']);


// Logout Route
Route::get('/logout', function () {
    session()->flush();
    return redirect()->route('login');
});

// Landing page contact form submission route
Route::post('/contact', [AuthController::class, 'contact'])->name('contact.send');

// ==========================================
// ROLE-BASED PORTAL ROUTES
// ==========================================

// Customer Portal Route
Route::get('/Customer/index', [CustomerController::class, 'index']);
Route::get('/Customer/profile', [CustomerController::class, 'profile']);
Route::get('/Customer/profile/edit', [CustomerController::class, 'editProfile']);
Route::post('/Customer/profile/update', [CustomerController::class, 'updateProfile'])->name('customer.profile.update');



// Manager Portal Route
Route::get('/Manager/index', [ManagerController::class, 'index']);


// Employee Portal Route
Route::get('/Employee/index', function () {
    return view('Employee.index'); // maps to resources/views/Employee/index.blade.php
});
