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
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MyStoreController;

// TEMP-PROBE: local-only session seed for coming-soon verification.
Route::get('/__probe-login', function () {
    session(['customer' => [
        'id' => 1, 'name' => 'Aziz Muntasir', 'email' => 'aziz@example.test',
        'initials' => 'AM', 'customer_code' => 'CT-1', 'status' => 'active', 'balance' => 0,
    ]]);
    session(['domain_cart' => []]);
    return redirect('/mystore');
});

Route::get('/', function (\App\Services\ResellerClubClient $resellerClub) {
    $trustItems = array_values(array_filter(array_map('trim', explode(',', (string) \App\Models\Setting::get('trust_items')))));

    return view('home', [
        'catalog' => $resellerClub->catalog(),
        'heroProof' => array_slice($trustItems, 0, 3),
    ]);
})->name('landing');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/password/reset', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/password/email', [AuthController::class, 'sendPasswordResetLink'])->middleware('throttle:3,1')->name('password.email');
Route::get('/password/reset/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,10')
    ->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Email verification (sign-up -> verify link -> login details emailed)
Route::get('/email/verify', [AuthController::class, 'verificationNotice'])->name('verification.notice');
Route::post('/email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:3,1')->name('verification.resend');
// Guide section 5: one-time token link (replaces the old signed-URL route).
Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
// Green-tick confirmation shown right after a successful verification, then it
// auto-continues to the destination the user was on (e.g. checkout).
Route::get('/email/verified', [AuthController::class, 'verifiedNotice'])->name('email.verified');

// Linked from the public landing page (Blog, Offers, ...), so guests may see it.
Route::get('/coming-soon/{section?}', function ($section = 'This section') {
    return view('under-construction', ['section' => str_replace('-', ' ', ucfirst($section))]);
})->name('under.construction');

// Public "coming soon" landing pages for services that are not live yet.
// The slug is validated against config/coming_soon.php, so anything else
// 404s instead of rendering an empty page. `back` carries where the visitor
// came from so the primary button can return them exactly there.
Route::get('/services/{service}', function (string $service) {
    $services = config('coming_soon');

    abort_unless(is_array($services) && isset($services[$service]), 404);

    $back = (string) request()->query('back', '');
    // Only ever accept a local path: never redirect off-site.
    $back = str_starts_with($back, '/') && ! str_starts_with($back, '//') ? $back : null;

    return view('coming-soon', [
        'slug' => $service,
        'service' => $services[$service],
        'backUrl' => $back,
    ]);
})->name('services.coming-soon');

Route::get('/hosting/shared', [HostingController::class, 'shared'])->name('hosting.shared');
Route::get('/hosting/vps', [HostingController::class, 'vps'])->name('hosting.vps');
Route::get('/hosting/dedicated', [HostingController::class, 'dedicated'])->name('hosting.dedicated');
Route::get('/support', [CustomerController::class, 'support'])->name('support');
Route::get('/support/tickets/{ticket}', [CustomerController::class, 'ticket'])
    ->middleware('customer')
    ->name('support.ticket');
Route::post('/support/tickets', [CustomerController::class, 'createTicket'])
    ->middleware('throttle:5,1')
    ->name('support.create');

// Live chat (client-area widget + admin console) — guests chat from the landing page.
Route::get('/chat/messages', [ChatController::class, 'poll'])->name('chat.poll');
Route::post('/chat/messages', [ChatController::class, 'send'])->name('chat.send');

// Domain search, cart and checkout are available without a customer account.
Route::prefix('domains')->group(function () {
    Route::get('/', [DomainController::class, 'index'])->name('domains.index');
    Route::post('/search', [DomainController::class, 'search'])->name('domains.search');
    Route::post('/transfer', [DomainController::class, 'startTransfer'])
        ->middleware('throttle:10,1')
        ->name('domains.transfer');
    Route::post('/cart', [DomainController::class, 'addToCart'])->name('domains.cart');
    Route::get('/cart', [DomainController::class, 'cart'])->name('cart.index');
    Route::post('/cart/remove', [DomainController::class, 'removeFromCart'])->name('cart.remove');
    Route::get('/checkout', [DomainController::class, 'checkout'])->name('domains.checkout');
    Route::post('/checkout', [DomainController::class, 'placeOrder'])->name('domains.place-order');
    Route::post('/customer/lookup', [DomainController::class, 'lookupCustomer'])
        ->middleware('throttle:10,1')
        ->name('domains.customer.lookup');
});

// Client area: signed-in customers only.
Route::middleware(['web', 'customer'])->group(function () {
    Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');
    // Client-area "coming soon" page: sidebar service rows navigate here
    // (the public navbar/footer keep using /services/{service} instead).
    Route::get('/client/coming-soon/{service}', function (string $service) {
        $services = config('coming_soon');
        abort_unless(is_array($services) && isset($services[$service]), 404);

        $back = (string) request()->query('back', '');
        $back = str_starts_with($back, '/') && ! str_starts_with($back, '//') ? $back : null;

        return view('store.coming-soon', [
            'slug' => $service,
            'service' => $services[$service],
            'backUrl' => $back,
            'navActive' => $service,
        ]);
    })->name('client.coming-soon');

    // My Store: the All Items overview + one page per section (always
    // reachable, whether the customer owns anything in it or not).
    Route::get('/mystore', [MyStoreController::class, 'index'])->name('mystore');
    Route::get('/mystore/domains', [MyStoreController::class, 'domains'])->name('mystore.domains');
    Route::post('/mystore/domains/{item}/nameservers', [MyStoreController::class, 'updateNameservers'])
        ->where('item', '[0-9]+')
        ->middleware('throttle:5,1')
        ->name('mystore.domains.nameservers');
    Route::get('/mystore/hosting', [MyStoreController::class, 'hosting'])->name('mystore.hosting');
    Route::get('/mystore/{service}', [MyStoreController::class, 'service'])
        ->where('service', 'ssl|email|sms')
        ->name('mystore.service');
    Route::get('/mystore/invoices', [MyStoreController::class, 'invoices'])->name('mystore.invoices');
    Route::get('/mystore/payments', [MyStoreController::class, 'payments'])->name('mystore.payments');
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
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.view');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
});

// bKash direct checkout (popup) — see app/Services/BkashClient.php
Route::get('/bkash/pay', [BkashController::class, 'pay'])->name('bkash.pay');
Route::post('/bkash/create', [BkashController::class, 'create'])->name('bkash.create');
Route::post('/bkash/execute', [BkashController::class, 'execute'])->name('bkash.execute');
Route::post('/bkash/query', [BkashController::class, 'query'])->name('bkash.query');
Route::post('/bkash/cancel', [BkashController::class, 'cancel'])->name('bkash.cancel');

Route::get('/domains/confirmation/{reference}', [DomainController::class, 'confirmation'])->name('domains.confirmation');
Route::post('/send-message', [ContactController::class, 'sendMessage'])->name('contact.send');
Route::get('/admin/login', [AdminController::class, 'loginForm'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login.submit');
Route::get('/admin', [AdminController::class, 'customers'])->name('admin.dashboard');
Route::get('/admin/orders', [AdminController::class, 'dashboard'])->name('admin.orders');
Route::get('/admin/apis', [AdminController::class, 'apis'])->name('admin.apis');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
Route::post('/admin/orders/{order}/status', [AdminController::class, 'updateOrder'])->name('admin.orders.status');
Route::post('/admin/orders/{order}/retry-registration', [AdminController::class, 'retryOrderRegistration'])->name('admin.orders.retry-registration');
Route::post('/admin/orders/{order}/retry-transfer', [AdminController::class, 'retryOrderTransfer'])->name('admin.orders.retry-transfer');
Route::get('/admin/customers', [AdminController::class, 'customers'])->name('admin.customers');
Route::get('/admin/customers/{customer}/edit', [AdminController::class, 'editCustomer'])->name('admin.customers.edit');
Route::post('/admin/customers/{customer}', [AdminController::class, 'updateCustomer'])->name('admin.customers.update');
Route::post('/admin/customers/{customer}/resend-verification', [AdminController::class, 'resendCustomerVerification'])->name('admin.customers.resend-verification');
Route::post('/admin/customers/{customer}/delete', [AdminController::class, 'deleteCustomer'])->name('admin.customers.delete');
Route::get('/admin/content', [AdminController::class, 'content'])->name('admin.content');
Route::post('/admin/content', [AdminController::class, 'updateContent'])->name('admin.content.update');
Route::get('/admin/chat/{session?}', [AdminController::class, 'chat'])->name('admin.chat');
Route::get('/admin/chat-poll', [ChatController::class, 'adminPoll'])->name('admin.chat.poll');
Route::post('/admin/chat-reply', [ChatController::class, 'adminSend'])->name('admin.chat.send');
Route::post('/admin/topups/{topup}/approve', [AdminController::class, 'approveTopup'])->name('admin.topups.approve');
Route::post('/admin/topups/{topup}/reject', [AdminController::class, 'rejectTopup'])->name('admin.topups.reject');
Route::get('/admin/invoices', [AdminController::class, 'invoices'])->name('admin.invoices');
Route::get('/admin/invoices/{invoice}', [AdminController::class, 'showInvoice'])->name('admin.invoices.show');
Route::get('/admin/invoices/{invoice}/edit', [AdminController::class, 'editInvoice'])->name('admin.invoices.edit');
Route::get('/admin/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('admin.invoices.pdf');
Route::post('/admin/invoices/{invoice}', [AdminController::class, 'updateInvoice'])->name('admin.invoices.update');
Route::post('/admin/invoices/{invoice}/status', [AdminController::class, 'updateInvoiceStatus'])->name('admin.invoices.status');

/**
 * CMS: hosting plan cards (prices, features, per-feature availability,
 * billing terms) behind the shared / vps / dedicated hosting pages.
 */
Route::get('/admin/hosting-plans/create/{type}', [AdminController::class, 'createHostingPlan'])->where('type', 'shared|vps|dedicated')->name('admin.hosting.plans.create');
Route::post('/admin/hosting-plans', [AdminController::class, 'storeHostingPlan'])->name('admin.hosting.plans.store');
Route::get('/admin/hosting-plans/{type?}/{term?}', [AdminController::class, 'hostingPlans'])->where('type', 'shared|vps|dedicated')->where('term', 'monthly|yearly|twoYear|threeYear')->name('admin.hosting.plans');
Route::post('/admin/hosting-plans/{type}/{term}/save', [AdminController::class, 'saveHostingPlanTerm'])->where('type', 'shared|vps|dedicated')->where('term', 'monthly|yearly|twoYear|threeYear')->name('admin.hosting.plans.saveTerm');
Route::get('/admin/hosting-plans/{plan}/edit', [AdminController::class, 'editHostingPlan'])->name('admin.hosting.plans.edit');
Route::post('/admin/hosting-plans/{plan}', [AdminController::class, 'updateHostingPlan'])->name('admin.hosting.plans.update');
Route::post('/admin/hosting-plans/{plan}/visibility', [AdminController::class, 'toggleHostingPlanVisibility'])->name('admin.hosting.plans.visibility');
Route::post('/admin/hosting-plans/{plan}/delete', [AdminController::class, 'deleteHostingPlan'])->name('admin.hosting.plans.delete');
