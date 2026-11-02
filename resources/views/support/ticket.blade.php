<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Support ticket #' . $ticket->id])
    <link rel="stylesheet" href="{{ asset('css/support.css') }}?v={{ @filemtime(public_path('css/support.css')) }}">
</head>
<body class="support-page">
    @include('partials.site-navbar', ['navActive' => 'support'])
    <main>
        <section class="support-section support-ticket-detail">
            <div class="support-container">
                <a class="support-back-link" href="{{ route('support') }}">← Back to support</a>
                <div class="support-ticket-detail-card">
                    <div class="support-ticket-detail-head">
                        <div>
                            <span class="support-eyebrow">SUPPORT REQUEST #{{ $ticket->id }}</span>
                            <h1>{{ $ticket->subject }}</h1>
                            <p>Submitted {{ $ticket->created_at->format('d M Y, h:i A') }} · {{ ucfirst($ticket->priority) }} priority</p>
                        </div>
                        <b class="support-status support-status-{{ $ticket->status }}">{{ ucfirst($ticket->status) }}</b>
                    </div>
                    <div class="support-ticket-message">{{ $ticket->message }}</div>
                    <a class="support-submit support-detail-action" href="{{ route('support') }}">Send another request <b>→</b></a>
                </div>
            </div>
        </section>
    </main>
    @include('partials.site-footer')
    @include('partials.chat')
</body>
</html>
