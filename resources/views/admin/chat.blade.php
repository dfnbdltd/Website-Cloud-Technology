<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'Live chat'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}"><link rel="stylesheet" href="{{ asset('css/workspace.css') }}"><link rel="stylesheet" href="{{ asset('css/store.css') }}"></head><body class="domain-app"><aside class="domain-sidebar"><a class="domain-brand" href="{{ route('admin.dashboard') }}"><span class="domain-logo">H</span><span>HOST<span>SEBA</span></span></a><div class="domain-account"><small>OPERATIONS</small><strong>Admin console</strong><span>{{ session('admin.email') }}</span></div><nav><small>MANAGEMENT</small><a href="{{ route('admin.dashboard') }}"><b>▦</b> Orders</a><a class="active" href="{{ route('admin.chat') }}"><b>💬</b> Live chat</a><a href="{{ route('landing') }}"><b>↗</b> View website</a>
<a href="#topups"><b>৳</b> Top-ups</a>
</nav><div class="domain-side-bottom"><form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit">↪ Sign out</button></form></div></aside>
<main class="domain-main">
<header class="domain-topbar"><div><span class="crumb">Operations /</span><strong>Live chat</strong></div><span class="top-avatar">A</span></header>
<div class="domain-content standalone-content">
<span class="domain-kicker">SUPPORT</span><h1 class="workspace-title">Live chat.</h1><p class="workspace-subtitle">Answer customer conversations in real time. Unread counts refresh on page load.</p>
@if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
<div class="admin-chat-layout">
<section class="chat-conversations">
@forelse($sessions as $chat)
<a class="chat-conv {{ $selected === $chat->session_id ? 'active' : '' }}{{ $chat->unread ? ' unread' : '' }}" href="{{ route('admin.chat', ['session' => $chat->session_id]) }}">
<span class="chat-conv-avatar">{{ strtoupper(substr($chat->name, 0, 2)) }}</span>
<span class="chat-conv-meta"><strong>{{ $chat->name }}</strong><small>{{ $chat->last_message }}</small></span>
<span class="chat-conv-side"><small>{{ $chat->last_time }}</small>@if($chat->unread)<b>{{ $chat->unread }}</b>@endif</span>
</a>
@empty
<div class="table-empty">No conversations yet. Customers can start a chat from any client-area page.</div>
@endforelse
</section>
<section class="chat-thread-panel">
@if($selected)
<div class="chat-thread" id="chatThread">
@forelse($messages as $message)
<div class="chat-row {{ $message->sender === 'admin' ? 'me' : 'them' }}"><div class="chat-bubble">{{ $message->message }}<time>{{ $message->created_at->format('d M, h:i A') }}</time></div></div>
@empty
<div class="table-empty">No messages in this conversation yet.</div>
@endforelse
</div>
<form class="chat-input" id="adminChatForm" data-session="{{ $selected }}">
<input type="text" id="adminChatText" placeholder="Reply as support agent…" maxlength="2000" autocomplete="off">
<button type="submit" aria-label="Send reply">➤</button>
</form>
@else
<div class="table-empty">Select a conversation to start replying.</div>
@endif
</section>
</div>
<section id="topups" class="admin-topups">
<span class="domain-kicker">BILLING</span><h2>Pending balance top-ups</h2>
<div class="table-card">
<div class="table-head"><span>REFERENCE</span><span>CUSTOMER</span><span>AMOUNT</span><span>STATUS</span></div>
@forelse($topups as $topup)
<div class="table-row">
<strong>{{ $topup->reference }}</strong>
<span>{{ optional($topup->user)->name }} ({{ optional($topup->user)->customer_uid }})</span>
<span>৳ {{ number_format($topup->amount, 2) }} · {{ ucfirst($topup->method) }}@if($topup->transaction_id) · TrxID {{ $topup->transaction_id }}@endif</span>
@if($topup->status === 'pending')
<form method="POST" action="{{ route('admin.topups.approve', $topup) }}">@csrf<button class="mini-approve" type="submit">Approve</button></form>
<form method="POST" action="{{ route('admin.topups.reject', $topup) }}">@csrf<button class="mini-reject" type="submit">Reject</button></form>
@else
<b class="table-status {{ $topup->status }}">{{ ucfirst($topup->status) }}</b>
@endif
</div>
@empty
<div class="table-empty">No top-up requests.</div>
@endforelse
</div>
</section>
</div>
</main>
<script>
(function () {
  var form = document.getElementById('adminChatForm');
  if (!form) return;
  var session = form.getAttribute('data-session');
  var thread = document.getElementById('chatThread');
  var input = document.getElementById('adminChatText');
  var lastId = @json($messages->max('id') ?? 0);

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

  function render(msgs) {
    msgs.forEach(function (m) {
      if (m.id <= lastId) return;
      lastId = m.id;
      var row = document.createElement('div');
      row.className = 'chat-row ' + (m.sender === 'admin' ? 'me' : 'them');
      row.innerHTML = '<div class="chat-bubble">' + esc(m.message) + '<time>' + esc(m.time) + '</time></div>';
      thread.appendChild(row);
    });
    thread.scrollTop = thread.scrollHeight;
  }

  function poll() {
    fetch('{{ route('admin.chat.poll') }}?session=' + encodeURIComponent(session))
      .then(function (r) { return r.json(); })
      .then(function (d) { render(d.messages || []); })
      .catch(function () {});
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var text = input.value.trim();
    if (!text) return;
    input.value = '';
    fetch('{{ route('admin.chat.send') }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') },
      body: JSON.stringify({ session: session, message: text })
    }).then(function (r) { return r.json(); }).then(function () { poll(); }).catch(function () {});
  });

  thread.scrollTop = thread.scrollHeight;
  setInterval(poll, 4000);
})();
</script>
</body></html>
