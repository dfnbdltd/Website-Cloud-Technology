{{-- Live chat bubble + panel (customer side). Polls /chat/messages every 4s. --}}
<link rel="stylesheet" href="{{ asset('css/chat.css') }}">
<div class="chat-widget" id="chatWidget">
<button class="chat-fab" type="button" onclick="Chat.toggle()" aria-label="Open live chat">
<span class="chat-fab-icon">💬</span>
<span class="chat-fab-badge" id="chatBadge" hidden>0</span>
</button>
<div class="chat-panel" id="chatPanel" hidden>
<div class="chat-head">
<div class="chat-head-id">{{ strtoupper(substr(session('customer.name', 'G'), 0, 2)) }}</div>
<div class="chat-head-meta"><strong>CloudTech Support</strong><small>@if(session('customer')){{ session('customer.name') }} · {{ session('customer.uid') }}@else Guest — we usually reply within minutes @endif</small></div>
<button class="chat-close" type="button" onclick="Chat.toggle()" aria-label="Close chat">×</button>
</div>
<div class="chat-body" id="chatBody"><div class="chat-day">Live chat</div></div>
<form class="chat-input" id="chatForm">
<input type="text" id="chatText" placeholder="Type your message…" autocomplete="off" maxlength="2000">
<button type="submit" aria-label="Send message">➤</button>
</form>
</div>
</div>
<script>
window.Chat = (function () {
  var open = false, lastId = 0, lastSeenAdminId = 0, timer = null, unread = 0;
  var body = document.getElementById('chatBody');
  var panel = document.getElementById('chatPanel');
  var badge = document.getElementById('chatBadge');
  if (!body || !panel || !badge) return { toggle: function () {} };

  function setOpen(state) {
    open = state;
    panel.hidden = !open;
    if (open) {
      unread = 0; badge.hidden = true;
      poll();
      if (!timer) timer = setInterval(poll, 4000);
      setTimeout(function () { var t = document.getElementById('chatText'); if (t) t.focus(); }, 50);
    } else if (timer) { clearInterval(timer); timer = null; }
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function render(messages) {
    messages.forEach(function (m) {
      if (m.id <= lastId) return;
      lastId = m.id;
      if (m.sender === 'admin') {
        if (!open && m.id > lastSeenAdminId) unread++;
        lastSeenAdminId = m.id;
      }
      var row = document.createElement('div');
      row.className = 'chat-row ' + (m.sender === 'admin' ? 'them' : 'me');
      row.innerHTML = '<div class="chat-bubble">' + esc(m.message) +
        '<time>' + esc(m.time) + '</time></div>';
      body.appendChild(row);
    });
    if (open) unread = 0;
    if (unread > 0) { badge.textContent = unread; badge.hidden = false; } else { badge.hidden = true; }
    if (messages.length) body.scrollTop = body.scrollHeight;
  }

  function poll() {
    fetch('{{ route('chat.poll') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (d) { render(d.messages || []); })
      .catch(function () {});
  }

  function toggle() {
    setOpen(!open);
  }

  // Minimize on Escape or by clicking outside the widget.
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && open) setOpen(false);
  });
  document.addEventListener('click', function (e) {
    var widget = document.getElementById('chatWidget');
    if (open && widget && !widget.contains(e.target)) setOpen(false);
  });

  document.getElementById('chatForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var input = document.getElementById('chatText');
    var text = input.value.trim();
    if (!text) return;
    input.value = '';
    fetch('{{ route('chat.send') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
      },
      body: JSON.stringify({ message: text })
    }).then(function (r) { return r.json(); }).then(function () { poll(); }).catch(function () {});
  });

  return { toggle: toggle };
})();
</script>
