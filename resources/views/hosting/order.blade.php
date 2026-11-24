{{--
    The saved hosting order. Shown after the bKash payment is confirmed (paid),
    or when someone reopens an unpaid / cancelled order.

    Data: $order (HostingOrder), $invoice (Invoice|null, only for the invoice owner).
--}}
@php($sslxCssVersion = @md5_file(public_path('css/ssl-checkout.css')))
@php($logos = ['bkash' => 'pay-bkash.svg', 'nagad' => 'pay-nagad.svg'])
@php($method = strtolower((string) $order->payment_method))
@php($methodLabel = ['bkash' => 'bKash', 'nagad' => 'Nagad'][$method] ?? ucfirst($method))
@php($statusLabel = $order->statusLabel())
<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Hosting — Order'])
    <link rel="stylesheet" href="{{ asset('css/ssl-checkout.css') }}?v={{ $sslxCssVersion }}">
</head>
<body class="cs-page">
@include('partials.site-navbar', ['navActive' => null])

<main class="sslx">
    <section class="sslx-wrap sslx-done">
        <div class="sslx-done-card">
            <div class="sslx-done-icon" aria-hidden="true">{{ $order->isCancelled() ? '✕' : '✓' }}</div>
            <h1>{{ $order->isHosted() ? 'Your hosting is ready' : ($order->isPaid() ? 'Payment received' : ($order->isCancelled() ? 'This order was cancelled' : 'Your hosting order is saved')) }}</h1>
            <p>{{ $order->isHosted()
                ? 'Your domain ' . $order->domain . ' is now hosted. We have emailed the details to ' . $order->customer_email . '.'
                : ($order->isPaid()
                ? 'We are setting up your hosting. A receipt has been sent to ' . $order->customer_email . '.'
                : ($order->isCancelled() ? 'No payment was taken. You can place the order again any time.' : 'We will set up your hosting as soon as the payment is confirmed.')) }}</p>

            <div class="sslx-ref"><span>ORDER REFERENCE</span><strong>{{ $order->reference }}</strong></div>

            <div class="sslx-facts">
                <div><small>Plan</small><strong>{{ $order->plan_name }}</strong></div>
                <div><small>Product</small><strong>{{ $order->product }}</strong></div>
                <div><small>Domain</small><strong>{{ $order->domain }}</strong></div>
                <div><small>Billing</small><strong>{{ $order->period_label }}</strong></div>
                <div><small>Amount</small><strong>৳{{ \App\Services\HostingOrderService::money($order->total) }}</strong></div>
                <div><small>Payment</small><strong>@if(isset($logos[$method]))<img src="{{ asset('image/payments/' . $logos[$method]) }}" alt="">@endif{{ $methodLabel }}</strong></div>
                @if($order->isPaid() && $order->provider_payment_id)
                    <div><small>Transaction</small><strong>{{ $order->provider_payment_id }}</strong></div>
                @endif
                <div><small>Payment status</small><strong><span class="sslx-status">{{ $order->isPaid() ? 'Paid' : ($order->isCancelled() ? 'Cancelled' : 'Waiting for payment') }}</span></strong></div>
                <div><small>Order status</small><strong><span class="sslx-status">{{ $statusLabel }}</span></strong></div>
            </div>

            <div class="sslx-actions">
                @if($invoice && !$order->isCancelled())
                    <a class="sslx-btn is-wide" href="{{ route('invoices.view', $invoice) }}">View invoice {{ $invoice->invoice_number }}</a>
                @endif
                @if($order->isCancelled())
                    <a class="sslx-btn is-wide" href="{{ route('hosting.checkout') }}">Back to checkout</a>
                @endif
                <a class="sslx-btn is-ghost is-wide" href="{{ route('mystore.hosting') }}">Go to My Store</a>
            </div>
        </div>
    </section>
</main>

@include('partials.site-footer')
@include('partials.chat')
</body>
</html>
