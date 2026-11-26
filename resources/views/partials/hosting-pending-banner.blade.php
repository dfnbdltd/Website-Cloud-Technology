{{--
    Shown on the domain checkout / confirmation pages while a hosting plan is waiting
    in session('hosting_cart') for the domain the customer is registering or
    transferring (chosen on /hosting/connect). The two payments stay separate:
    after paying for the domain, the button goes back to hosting checkout, which
    re-checks that the domain is registered before it lets the hosting order continue.
--}}
@php($hostingPending = session('hosting_cart.pending_domain'))
@if(is_array($hostingPending) && !empty($hostingPending['domain']))
    <div class="hosting-pending-banner" role="status" style="margin:0 0 18px;padding:14px 18px;border:1px solid #cfe0fa;border-radius:12px;background:#f1f6ff;color:#1f3a63;font-size:14px;line-height:1.55;text-align:left">
        <strong>Your hosting plan is waiting.</strong>
        Finish and pay for <b>{{ $hostingPending['domain'] }}</b> here, then continue to your hosting checkout.
        <a href="{{ \App\Support\ReturnTo::url(route('hosting.checkout', [], false)) }}" style="display:inline-block;margin-left:6px;font-weight:700;color:#175ac4">Continue to hosting checkout →</a>
    </div>
@endif
