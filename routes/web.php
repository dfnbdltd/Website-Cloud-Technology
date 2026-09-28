<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BkashController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Auth\VerificationController;

Route::get('/', function (\App\Services\ResellerClubClient $resellerClub) {
    return view('home', ['catalog' => $resellerClub->catalog()]);
})->name('landing');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify')->middleware('signed');
Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');

Route::middleware(['customer.verified'])->group(function () {
    Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');
    Route::get('/coming-soon/{section?}', function ($section = 'This section') {
        return view('under-construction', ['section' => str_replace('-', ' ', ucfirst($section))]);
    })->name('under.construction');
    Route::get('/invoices', [CustomerController::class, 'invoices'])->name('invoices');
    Route::get('/support', [CustomerController::class, 'support'])->name('support');
    Route::post('/support/tickets', [CustomerController::class, 'createTicket'])->name('support.create');

    Route::prefix('domains')->group(function () {
        Route::get('/', [DomainController::class, 'index'])->name('domains.index');
        Route::post('/search', [DomainController::class, 'search'])->name('domains.search');
        Route::post('/cart', [DomainController::class, 'addToCart'])->name('domains.cart');
        Route::get('/checkout', [DomainController::class, 'checkout'])->name('domains.checkout');
        Route::post('/checkout', [DomainController::class, 'placeOrder'])->name('domains.place-order');
    });
});

Route::get('/bkash/create-payment', [BkashController::class, 'createPayment'])->name('bkash.create-payment');
Route::get('/bkash/callback', [BkashController::class, 'callback'])->name('bkash.callback');
Route::get('/bkash/query/{paymentId}', [BkashController::class, 'query'])->name('bkash.query');
Route::post('/send-message', [ContactController::class, 'sendMessage'])->name('contact.send');
Route::get('/admin/login', [AdminController::class, 'loginForm'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login.submit');
Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
Route::post('/admin/orders/{order}/status', [AdminController::class, 'updateOrder'])->name('admin.orders.status');
