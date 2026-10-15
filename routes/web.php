<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BkashController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\HostingController;

Route::get('/', function (\App\Services\ResellerClubClient $resellerClub) {
    return view('home', ['catalog' => $resellerClub->catalog()]);
})->name('landing');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/password/reset', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/password/email', [AuthController::class, 'sendPasswordResetLink'])->middleware('throttle:3,1')->name('password.email');
Route::get('/password/reset/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Email verification (sign-up -> verify link -> login details emailed)
Route::get('/email/verify', [AuthController::class, 'verificationNotice'])->name('verification.notice');
Route::post('/email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:3,1')->name('verification.resend');
// Guide section 5: one-time token link (replaces the old signed-URL route).
Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verification.verify');

// Linked from the public landing page (Blog, Offers, ...), so guests may see it.
Route::get('/coming-soon/{section?}', function ($section = 'This section') {
    return view('under-construction', ['section' => str_replace('-', ' ', ucfirst($section))]);
})->name('under.construction');

Route::get('/hosting/shared', [HostingController::class, 'shared'])->name('hosting.shared');
Route::get('/hosting/vps', [HostingController::class, 'vps'])->name('hosting.vps');
Route::get('/hosting/dedicated', [HostingController::class, 'dedicated'])->name('hosting.dedicated');

// Live chat (client-area widget + admin console) — guests chat from the landing page.
Route::get('/chat/messages', [ChatController::class, 'poll'])->name('chat.poll');
Route::post('/chat/messages', [ChatController::class, 'send'])->name('chat.send');

// Client area: signed-in customers only.
Route::middleware(['web', 'customer'])->group(function () {
    Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');
    Route::get('/mystore', [CustomerController::class, 'myStore'])->name('mystore');
    Route::get('/store/hosting/{type}', [HostingController::class, 'clientPlans'])->where('type', 'shared|vps|dedicated')->name('client.hosting');
    Route::post('/mystore/add-balance', [CustomerController::class, 'addBalance'])->name('mystore.add-balance');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/bkash', [WalletController::class, 'start'])->name('wallet.bkash.start');
    Route::get('/wallet/bkash', [WalletController::class, 'pay'])->name('wallet.bkash.pay');
    Route::post('/wallet/bkash/create', [WalletController::class, 'create'])->name('wallet.bkash.create');
    Route::post('/wallet/bkash/execute', [WalletController::class, 'execute'])->name('wallet.bkash.execute');
    Route::post('/wallet/bkash/query', [WalletController::class, 'query'])->name('wallet.bkash.query');
    Route::get('/profile/settings', [CustomerController::class, 'profileSettings'])->name('profile.settings');
    Route::post('/profile/settings', [CustomerController::class, 'updateProfile'])->name('profile.update');
    Route::get('/profile/password', [CustomerController::class, 'changePasswordForm'])->name('change.password');
    Route::post('/profile/password', [CustomerController::class, 'changePassword'])->name('change.password.submit');
    Route::get('/invoices', [CustomerController::class, 'invoices'])->name('invoices');
    Route::get('/support', [CustomerController::class, 'support'])->name('support');
    Route::post('/support/tickets', [CustomerController::class, 'createTicket'])->name('support.create');

    Route::prefix('domains')->group(function () {
        Route::get('/', [DomainController::class, 'index'])->name('domains.index');
        Route::post('/search', [DomainController::class, 'search'])->name('domains.search');
        Route::post('/cart', [DomainController::class, 'addToCart'])->name('domains.cart');
        Route::get('/cart', [DomainController::class, 'cart'])->name('cart.index');
        Route::post('/cart/remove', [DomainController::class, 'removeFromCart'])->name('cart.remove');
        Route::get('/checkout', [DomainController::class, 'checkout'])->name('domains.checkout');
        Route::post('/checkout', [DomainController::class, 'placeOrder'])->name('domains.place-order');
    });
});

// bKash direct checkout (popup) — see app/Services/BkashClient.php
Route::get('/bkash/pay', [BkashController::class, 'pay'])->name('bkash.pay');
Route::post('/bkash/create', [BkashController::class, 'create'])->name('bkash.create');
Route::post('/bkash/execute', [BkashController::class, 'execute'])->name('bkash.execute');
Route::post('/bkash/query', [BkashController::class, 'query'])->name('bkash.query');

Route::get('/domains/confirmation/{reference}', [DomainController::class, 'confirmation'])->name('domains.confirmation');
Route::post('/send-message', [ContactController::class, 'sendMessage'])->name('contact.send');
Route::get('/admin/login', [AdminController::class, 'loginForm'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login.submit');
Route::get('/admin', [AdminController::class, 'customers'])->name('admin.dashboard');
Route::get('/admin/orders', [AdminController::class, 'dashboard'])->name('admin.orders');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
Route::post('/admin/orders/{order}/status', [AdminController::class, 'updateOrder'])->name('admin.orders.status');
Route::get('/admin/customers', [AdminController::class, 'customers'])->name('admin.customers');
Route::get('/admin/customers/{customer}/edit', [AdminController::class, 'editCustomer'])->name('admin.customers.edit');
Route::post('/admin/customers/{customer}', [AdminController::class, 'updateCustomer'])->name('admin.customers.update');
Route::get('/admin/content', [AdminController::class, 'content'])->name('admin.content');
Route::post('/admin/content', [AdminController::class, 'updateContent'])->name('admin.content.update');
Route::get('/admin/chat/{session?}', [AdminController::class, 'chat'])->name('admin.chat');
Route::get('/admin/chat-poll', [ChatController::class, 'adminPoll'])->name('admin.chat.poll');
Route::post('/admin/chat-reply', [ChatController::class, 'adminSend'])->name('admin.chat.send');
Route::post('/admin/topups/{topup}/approve', [AdminController::class, 'approveTopup'])->name('admin.topups.approve');
Route::post('/admin/topups/{topup}/reject', [AdminController::class, 'rejectTopup'])->name('admin.topups.reject');
