{{--
    Step 3: the saved order. Payment (bKash / Nagad) is confirmed later; until
    then the order waits as "payment pending".

    Data: $order (SslOrder), $invoice (Invoice|null, only for the invoice owner).
--}}
@php($sslxCssVersion = @md5_file(public_path('css/ssl-checkout.css')))
@php($logos = ['bkash' => 'pay-bkash.svg', 'nagad' => 'pay-nagad.svg'])
@php($method = strtolower((string) $order->payment_method))
@php($methodLabel = ['bkash' => 'bKash', 'nagad' => 'Nagad'][$method] ?? ucfirst($method))
<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'SSL Certificate — Order saved'])
    <link rel="stylesheet" href="{{ asset('css/ssl-checkout.css') }}?v={{ $sslxCssVersion }}">
</head>
<body class="cs-page">
@include('partials.site-navbar', ['navActive' => null])

<main class="sslx">
    <section class="sslx-wrap sslx-done">
        <div class="sslx-done-card">
            <div class="sslx-done-icon" aria-hidden="true">✓</div>
            <h1>{{ $order->isPaid() ? 'Payment confirmed — thank you!' : 'Your SSL order is saved' }}</h1>
            <p>{{ $order->isPaid()
                ? 'Your payment is confirmed and your certificate is being set up.'
                : 'We will start setting up your certificate as soon as the payment is confirmed.' }}</p>

            <div class="sslx-ref"><span>ORDER REFERENCE</span><strong>{{ $order->reference }}</strong></div>

            <div class="sslx-facts">
                <div><small>Certificate</small><strong>{{ $order->plan_name }}</strong></div>
                <div><small>Domain</small><strong>{{ $order->domain }}</strong></div>
                <div><small>Term</small><strong>{{ $order->years }} {{ $order->years > 1 ? 'years' : 'year' }}</strong></div>
                <div><small>Amount</small><strong>৳{{ \App\Support\SslPricing::money($order->total) }}</strong></div>
                <div><small>Payment</small><strong>@if(isset($logos[$method]))<img src="{{ asset('image/payments/' . $logos[$method]) }}" alt="">@endif{{ $methodLabel }}</strong></div>
                <div><small>Status</small><strong><span class="sslx-status">{{ $order->isPaid() ? 'Paid' : 'Waiting for payment' }}</span></strong></div>
            </div>

            <div class="sslx-actions">
                @if($invoice)
                    <a class="sslx-btn is-wide" href="{{ route('invoices.view', $invoice) }}">View invoice {{ $invoice->invoice_number }}</a>
                @endif
                <a class="sslx-btn is-ghost is-wide" href="{{ route('mystore.service', 'ssl') }}">Go to My Store</a>
            </div>
        </div>
    </section>
</main>

@include('partials.site-footer')
@include('partials.chat')
</body>
</html>
