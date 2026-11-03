{{--
    Public site navbar — the exact navbar of the home page, shared by every
    public page (top strip + sticky header). Styles live in
    public/css/site-navbar.css, scoped under #site-navbar so a page's own
    CSS can never change how it looks.

    @include('partials.site-navbar', ['navActive' => 'home' | 'domains' | 'hosting' | 'support' | null])
--}}
@php($navActive = $navActive ?? null)
@php($navCartCount = count(session('domain_cart', [])))
@php($navCssVersion = @md5_file(public_path('css/site-navbar.css')))
@php($navSelected = function ($item) use ($navActive) { return $navActive === $item ? ' class="selected"' : ''; })
<link rel="stylesheet" href="{{ asset('css/site-navbar.css') }}@if($navCssVersion)?v={{ $navCssVersion }}@endif">
<div class="site-navbar" id="site-navbar">
<header class="site-header"><div class="site-wrap site-header-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'h'])</a>
{{-- Hamburger: shown only on small screens. aria-expanded/aria-controls let
     screen readers and keyboards drive the same panel as touch users. --}}
<button class="site-nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="siteNavPanel">
<span class="site-nav-toggle-bar" aria-hidden="true"></span>
<span class="site-nav-toggle-bar" aria-hidden="true"></span>
<span class="site-nav-toggle-bar" aria-hidden="true"></span>
</button>
<nav class="site-nav" id="siteNavPanel"><a{!! $navSelected('home') !!} href="{{ route('landing') }}">Home</a><div class="site-nav-dropdown"><a{!! $navSelected('domains') !!} href="{{ route('domains.index') }}">Domains</a><div class="site-submenu"><a href="{{ route('domains.index') }}">Domain Registration</a><a class="coming-soon-link" href="{{ route('services.coming-soon', 'domain-transfer') }}?back={{ urlencode(request()->getRequestUri()) }}">Domain Transfer</a></div></div><div class="site-nav-dropdown"><a{!! $navSelected('hosting') !!} href="{{ route('hosting.shared') }}">Web Hosting</a><div class="site-submenu"><a href="{{ route('hosting.shared') }}">Shared Hosting</a><a href="{{ route('hosting.vps') }}">VPS Hosting</a><a href="{{ route('hosting.dedicated') }}">Dedicated Hosting</a></div></div><a class="coming-soon-link" href="{{ route('services.coming-soon', 'ssl-certificate') }}?back={{ urlencode(request()->getRequestUri()) }}">SSL Certificate</a><a class="coming-soon-link" href="{{ route('services.coming-soon', 'business-email') }}?back={{ urlencode(request()->getRequestUri()) }}">Business Email</a><a class="coming-soon-link" href="{{ route('services.coming-soon', 'sms-service') }}?back={{ urlencode(request()->getRequestUri()) }}">SMS Service</a><a{!! $navSelected('support') !!} href="{{ route('support') }}">Support</a></nav><div class="site-account"><a class="site-phone" href="tel:{{ preg_replace('/[^0-9+]/', '', \App\Models\Setting::get('support_phone')) }}" aria-label="Call {{ \App\Models\Setting::get('support_phone') }}">☎ <span>{{ \App\Models\Setting::get('support_phone') }}</span></a>@if(session('customer'))@include('partials.client-area-menu')<button class="site-user" type="button" onclick="event.stopPropagation(); document.getElementById('accountMenu').classList.toggle('open');">@if(session('customer.logo'))<img class="site-avatar" src="{{ session('customer.logo') }}" alt="">@else<span class="site-avatar">{{ session('customer.initials') ?: \App\User::initials(session('customer.name')) }}</span>@endif<span>{{ session('customer.name') }}</span> ⌄</button><div class="site-menu" id="accountMenu"><div class="site-menu-head"><strong>{{ session('customer.name') }}</strong><span>{{ session('customer.email') }}</span></div><a href="{{ route('dashboard') }}">Dashboard</a><a href="{{ route('mystore') }}">My Store</a><a href="{{ route('wallet.index') }}">Wallet · ৳{{ number_format(session('customer.balance', 0), 2) }}</a><a href="{{ route('invoices') }}">Invoices</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form></div>@else<a class="site-btn-ghost" href="{{ route('login') }}">Log in</a>@endif</div></div></header>
</div>
@include('partials.coming-soon-modal')
<script>
(function(){
var root=document.getElementById('site-navbar');
if(!root){return;}
var toggle=root.querySelector('.site-nav-toggle');
var nav=root.querySelector('#siteNavPanel');

/* Account menu (existing behaviour, kept). */
document.addEventListener('click',function(e){var m=document.getElementById('accountMenu');if(m&&!m.contains(e.target)){m.classList.remove('open');}});

if(!toggle||!nav){return;}

function setOpen(open){
  root.classList.toggle('nav-open',open);
  toggle.setAttribute('aria-expanded',open?'true':'false');
  toggle.setAttribute('aria-label',open?'Close menu':'Open menu');
  document.body.classList.toggle('nav-menu-open',open);
}

toggle.addEventListener('click',function(e){
  e.stopPropagation();
  setOpen(!root.classList.contains('nav-open'));
});

/* On the collapsed panel, a tap on "Domains"/"Web Hosting" expands its
   submenu instead of leaving the page. Only applies while the panel is open
   (small screens); on desktop the same links navigate as before. */
nav.addEventListener('click',function(e){
  if(!root.classList.contains('nav-open')){return;}
  var parent=e.target.closest('.site-nav-dropdown');
  if(!parent){return;}
  var link=e.target.closest('a');
  if(!link||link.parentNode!==parent){return;}
  e.preventDefault();
  var wasOpen=parent.classList.contains('open');
  /* accordion: close siblings so the panel never grows into a wall of text */
  nav.querySelectorAll('.site-nav-dropdown.open').forEach(function(d){
    if(d!==parent){d.classList.remove('open');}
  });
  parent.classList.toggle('open',!wasOpen);
});

/* Tapping a real destination closes the panel. Coming-soon services are now
   real pages (/services/...), so they close it like any other link. */
nav.addEventListener('click',function(e){
  var link=e.target.closest('a');
  if(link&&!link.parentNode.classList.contains('site-nav-dropdown')){
    setOpen(false);
  }
});

/* Escape closes, and clicking the page behind the panel closes too. */
document.addEventListener('keydown',function(e){
  if(e.key==='Escape'&&root.classList.contains('nav-open')){
    setOpen(false);
    toggle.focus();
  }
});
document.addEventListener('click',function(e){
  if(root.classList.contains('nav-open')&&!root.contains(e.target)){setOpen(false);}
});

/* Leaving small-screen sizes must never strand the page in the locked state. */
var mq=window.matchMedia('(max-width: 960px)');
function sync(e){ if(!e.matches){ setOpen(false); } }
if(mq.addEventListener){mq.addEventListener('change',sync);}else if(mq.addListener){mq.addListener(sync);}
})();
</script>
