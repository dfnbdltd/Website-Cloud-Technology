<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Support'])
    <link rel="stylesheet" href="{{ asset('css/support.css') }}?v={{ @filemtime(public_path('css/support.css')) }}">
</head>
<body class="support-page">
    @include('partials.site-navbar', ['navActive' => 'support'])

    <main>
        <section class="support-hero">
            <div class="support-container support-hero-grid">
                <div>
                    <span class="support-eyebrow">CUSTOMER CARE</span>
                    <h1>Let’s get your issue <em>sorted.</em></h1>
                    <p>Tell us what happened and our support team will review your request and get back to you with a clear next step.</p>
                    <div class="support-promises">
                        <span><b>✓</b> Personal response</span>
                        <span><b>✓</b> Secure handling</span>
                        <span><b>✓</b> Friendly guidance</span>
                    </div>
                </div>
                <div class="support-hero-card">
                    <strong>Need help right now?</strong>
                    <p>Include your domain, order reference, or account email so we can help faster.</p>
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', \App\Models\Setting::get('support_phone')) }}">☎ {{ \App\Models\Setting::get('support_phone') }}</a>
                </div>
            </div>
        </section>

        <section class="support-section">
            <div class="support-container support-layout">
                <div class="support-form-card">
                    @if(session('success'))
                        <div class="support-alert support-alert-success">{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="support-alert support-alert-error">{{ $errors->first() }}</div>
                    @endif
                    <div class="support-card-heading">
                        <span class="support-eyebrow">SEND A MESSAGE</span>
                        <h2>How can we help?</h2>
                        <p>Share a few details and we’ll take it from there.</p>
                    </div>
                    <form method="POST" action="{{ route('support.create') }}" class="support-form">
                        @csrf
                        @if(!$customer)
                            <div class="support-form-row">
                                <label>Full name<input name="name" value="{{ old('name') }}" placeholder="Your name" required></label>
                                <label>Email address<input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required></label>
                            </div>
                            <label>Phone number<input name="phone" value="{{ old('phone') }}" placeholder="+880 1XXX XXX XXX" required></label>
                        @else
                            <div class="support-signed-in">Sending as <strong>{{ $customer['name'] }}</strong><span>{{ $customer['email'] }}</span></div>
                        @endif
                        <div class="support-form-row">
                            <label>What do you need help with?<input name="subject" value="{{ old('subject') }}" placeholder="For example: Domain or billing issue" required></label>
                            <label>Priority<select name="priority"><option value="normal">Normal priority</option><option value="high" @if(old('priority') === 'high') selected @endif>High priority</option><option value="low" @if(old('priority') === 'low') selected @endif>Low priority</option></select></label>
                        </div>
                        <label>Describe your issue<textarea name="message" rows="7" placeholder="Please include any useful details, order number, or error message..." required>{{ old('message') }}</textarea></label>
                        <button class="support-submit" type="submit">Send support request <b>→</b></button>
                        <small class="support-privacy">Your information is only used to respond to this support request.</small>
                    </form>
                </div>

                <aside class="support-aside">
                    <div class="support-aside-card">
                        <span class="support-eyebrow">BEFORE YOU SEND</span>
                        <h2>A better first reply starts with context.</h2>
                        <ul>
                            <li><b>01</b><span>Tell us which service or domain is affected.</span></li>
                            <li><b>02</b><span>Include an order or invoice reference when relevant.</span></li>
                            <li><b>03</b><span>Describe what you expected and what happened instead.</span></li>
                        </ul>
                    </div>
                    @if($customer)
                        <div class="support-ticket-card">
                            <div><span class="support-eyebrow">YOUR REQUESTS</span><strong>{{ $tickets->count() }} ticket{{ $tickets->count() === 1 ? '' : 's' }}</strong></div>
                            <a href="#recent-tickets">View history ↓</a>
                        </div>
                    @else
                        <div class="support-ticket-card"><strong>Already have an account?</strong><span>Log in to keep a visible support history.</span><a href="{{ route('login') }}">Log in →</a></div>
                    @endif
                </aside>
            </div>
        </section>

        @if($customer)
            <section class="support-section support-history" id="recent-tickets">
                <div class="support-container">
                    <div class="support-card-heading"><span class="support-eyebrow">SUPPORT HISTORY</span><h2>Your recent requests</h2></div>
                    <div class="support-ticket-list">
                        @forelse($tickets as $ticket)
                            <a class="support-ticket-row" href="{{ route('support.ticket', $ticket) }}"><div><strong>#{{ $ticket->id }} · {{ $ticket->subject }}</strong><span>{{ $ticket->created_at->format('d M Y, h:i A') }} · {{ ucfirst($ticket->priority) }} priority</span></div><b class="support-status support-status-{{ $ticket->status }}">{{ ucfirst($ticket->status) }}</b><i aria-hidden="true">→</i></a>
                        @empty
                            <div class="support-empty">You have no previous support requests.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif
    </main>
    @include('partials.site-footer')
    @include('partials.chat')
</body>
</html>
