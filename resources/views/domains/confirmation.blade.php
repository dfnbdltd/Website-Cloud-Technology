<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'Order received'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}"><style>
body:has(.confirmation-page){background:#f3f7fc}
body:has(.confirmation-page):before,body:has(.confirmation-page):after{content:'';position:fixed;z-index:-1;border-radius:50%;pointer-events:none;filter:blur(3px);opacity:.7;animation:confFloat 11s ease-in-out infinite alternate}
body:has(.confirmation-page):before{width:430px;height:430px;left:-180px;top:80px;background:#dcecff}
body:has(.confirmation-page):after{width:360px;height:360px;right:-140px;bottom:-80px;background:#dff7ee;animation-delay:-4s}
@keyframes confFloat{to{transform:translate(24px,-20px) scale(1.08)}}
.confirmation-page{max-width:860px;padding:34px 20px 70px}
.confirmation-page .checkout-brand{position:relative;display:flex;align-items:center;justify-content:center;width:100%;height:92px;padding:0 10px 20px;border-bottom:1px solid #d9e5f2;box-sizing:border-box}
.confirmation-page .checkout-brand:after{content:'';position:absolute;left:50%;bottom:-1px;width:132px;height:2px;transform:translateX(-50%) scaleX(0);transform-origin:center;background:linear-gradient(90deg,#2c7bed,#45b7ed,#27b681);border-radius:3px;animation:confBrandLine .8s .35s cubic-bezier(.22,.61,.36,1) forwards}
.confirmation-page .checkout-brand .ct-logo-img{width:auto;height:62px;filter:drop-shadow(0 8px 14px rgba(37,111,205,.14));animation:confBrandEnter .75s cubic-bezier(.22,.61,.36,1) both,confBrandFloat 5s 1s ease-in-out infinite}
@keyframes confBrandEnter{from{opacity:0;transform:translateY(-10px) scale(.9)}to{opacity:1;transform:translateY(0) scale(1)}}
@keyframes confBrandFloat{0%,100%{filter:drop-shadow(0 8px 14px rgba(37,111,205,.14));transform:translateY(0)}50%{filter:drop-shadow(0 12px 20px rgba(37,111,205,.22));transform:translateY(-3px)}}
@keyframes confBrandLine{to{transform:translateX(-50%) scaleX(1)}}
.confirmation-card{position:relative;margin-top:28px;padding:44px 58px 42px;border:1px solid #dce7f3;border-radius:22px;background:rgba(255,255,255,.96);box-shadow:0 22px 60px rgba(34,72,115,.12);overflow:hidden;animation:confEnter .65s cubic-bezier(.2,.8,.2,1) both}
.confirmation-card:before{content:'';position:absolute;inset:0 0 auto;height:5px;background:linear-gradient(90deg,#2c7bed,#49baf3,#27b681)}
@keyframes confEnter{from{opacity:0;transform:translateY(18px) scale(.98)}to{opacity:1;transform:none}}
.confirmation-icon{width:72px;height:72px;margin:0 auto 20px;border-radius:50%;display:grid;place-items:center;background:#e4f8f0;color:#20ad7b;font-size:39px;font-weight:500;box-shadow:0 0 0 10px #f0fcf7;animation:confPop .7s .2s both}
@keyframes confPop{from{transform:scale(.6);opacity:0}to{transform:scale(1);opacity:1}}
.confirmation-card .domain-kicker{color:#347bea;letter-spacing:2px}
.confirmation-card h1{font-size:36px;line-height:1.12;letter-spacing:-1.5px;margin:15px 0 9px}
.confirmation-card>p{max-width:570px;margin:0 auto;color:#70839a;font-size:15px;line-height:1.7}
.order-reference{margin:22px 0 10px;padding:17px 20px;border:1px solid #dbe8f5;border-radius:14px;background:linear-gradient(135deg,#f7fbff,#eff7ff)}
.order-reference span{display:block;color:#8da0b5;font-size:10px;font-weight:800;letter-spacing:1.5px}
.order-reference strong{display:block;color:#17345c;font-size:21px;margin-top:6px;letter-spacing:.3px}
.confirmation-meta{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:0 0 26px}
.confirmation-meta>div{padding:14px 8px;border-bottom:1px solid #edf2f7}
.confirmation-meta small{display:block;color:#8da0b5;font-size:11px;margin-bottom:5px}
.confirmation-meta strong{display:block;color:#19375e;font-size:14px;word-break:break-word}
.payment-brand{display:flex;align-items:center;justify-content:center;gap:10px;padding:12px 14px!important;border:1px solid #e6edf6;border-radius:12px;background:#fbfdff}
.payment-brand img{width:auto;height:25px;max-width:92px;object-fit:contain}
.payment-brand strong{font-size:13px!important}
.confirmation-meta .checkout-submit{grid-column:1/-1;margin-top:12px}
.confirmation-card>.checkout-submit{display:flex;justify-content:center;align-items:center;width:100%;text-decoration:none}
.confirmation-actions{display:grid;gap:12px;margin-top:10px}
.confirmation-actions .checkout-submit{margin:0}
.confirmation-actions .checkout-submit.payment-action{background:linear-gradient(135deg,#d41469,#ec3d83);box-shadow:0 14px 28px rgba(212,20,105,.24)}
.confirmation-actions .checkout-submit.payment-action:hover{box-shadow:0 18px 34px rgba(212,20,105,.32)}
.payment-help{grid-column:1/-1;margin-top:12px;padding:15px;border:1px solid #e4edf7;border-radius:12px;background:#f8fbff;color:#6e8299;font-size:13px;line-height:1.55}
.confirmation-card .domain-alert.error{border:1px solid #f5caca;border-radius:12px;background:#fff6f6;color:#b64747;padding:13px 16px;font-size:13px;line-height:1.55}
@media(max-width:620px){.confirmation-card{padding:42px 22px 34px}.confirmation-card h1{font-size:31px}.confirmation-meta{grid-template-columns:1fr}.confirmation-meta>div{padding:12px 0}.confirmation-page{padding-left:14px;padding-right:14px}.confirmation-page .checkout-brand{height:82px}.confirmation-page .checkout-brand .ct-logo-img{height:54px}}
@media(prefers-reduced-motion:reduce){body:has(.confirmation-page):before,body:has(.confirmation-page):after,.confirmation-card,.confirmation-icon,.confirmation-page .checkout-brand .ct-logo-img,.confirmation-page .checkout-brand:after{animation:none;transform:none}}
@php($paymentMethod = strtolower($order['payment']['method'] ?? ''))
@php($paymentLogo = $paymentMethod === 'bkash' ? 'pay-bkash.svg' : ($paymentMethod === 'nagad' ? 'pay-nagad.svg' : ($paymentMethod === 'bank' ? 'pay-bank.svg' : null)))
</style></head><body class="domain-app"><main class="confirmation-page"><a class="domain-brand checkout-brand" href="{{ session('customer') ? route('dashboard') : route('landing') }}">@include('partials.logo', ['gid' => 'k'])</a><div class="confirmation-card"><div class="confirmation-icon">✓</div><span class="domain-kicker">{{ ($paid ?? false) ? 'PAYMENT RECEIVED' : 'ORDER RECEIVED' }}</span>@if(isset($gatewayWarning))<div class="domain-alert error" style="text-align:left;margin-top:18px">{{ $gatewayWarning }}</div>@endif<h1>{{ ($paid ?? false) ? 'Payment confirmed — thank you!' : "You're almost there." }}</h1>@if($paid ?? false)@if($registrationPending ?? false)<p>Your payment has been confirmed, but domain registration is pending. Please contact support with your order reference.</p>@elseif($isTransfer ?? false)@if($transferPending ?? false)<p>Your payment is confirmed and the transfer request has been submitted to the registrar. Approve the confirmation email at your administrative contact. Transfers typically take 5&ndash;7 days.</p>@elseif($transferFailed ?? false)<p>Your payment is confirmed, but the registrar did not accept the transfer request. Please contact support with your order reference so we can assist.</p>@elseif($transferComplete ?? false)<p>Your domain transfer is complete. If you are also moving your hosting, review your DNS or nameserver settings.</p>@else<p>Your payment is confirmed. The transfer request is being processed; we will update you when the registrar responds.</p>@endif @else<p>Your payment has been confirmed and the order is active. A receipt is on its way to your email.</p>@endif @else<p>Your domain order has been created and is waiting for payment confirmation. Our billing team will update the order after the gateway callback.</p>@endif<div class="order-reference"><span>ORDER REFERENCE</span><strong>{{ $order['reference'] }}</strong></div><div class="confirmation-meta"><div><small>Domain</small><strong>{{ $order['items'][0]['domain'] }}</strong></div><div><small>Amount</small><strong>৳ {{ number_format($order['total']) }}</strong></div><div class="payment-brand"><small>Payment</small>@if($paymentLogo)<img src="{{ asset('image/payments/' . $paymentLogo) }}" alt="{{ ucfirst($paymentMethod) }}">@endif<strong>{{ ucfirst($paymentMethod) }}</strong></div>@if($paid ?? false)@if(!empty($order['payment']['transaction_id']))<div><small>{{ ucfirst($paymentMethod) }} transaction</small><strong>{{ $order['payment']['transaction_id'] }}</strong></div>@endif @else @if($paymentMethod === 'bkash')<div class="confirmation-actions"><a class="checkout-submit payment-action" href="{{ route('bkash.pay', ['invoice' => $order['reference']]) }}">Pay now with bKash <b>→</b></a></div>@else<div class="payment-help">Our billing team will contact you with {{ ucfirst($paymentMethod) }} payment instructions.</div>@endif @endif</div><a class="checkout-submit" href="{{ session('customer') ? route('dashboard') : route('landing') }}">Return {{ session('customer') ? 'to dashboard' : 'to home' }} <b>→</b></a></div></main>
@include('partials.chat')
</body></html>