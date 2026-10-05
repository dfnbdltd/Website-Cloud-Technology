<link rel="stylesheet" href="{{ asset('css/revechat.css') }}?v={{ filemtime(public_path('css/revechat.css')) }}">
<button
  type="button"
  class="revechat-fab"
  id="reveChatFab"
  aria-label="Chat with us"
  aria-haspopup="dialog"
  data-revechat-account="{{ config('services.revechat.account') }}"
>
  <span class="revechat-fab-icon" aria-hidden="true">
    <svg viewBox="0 0 24 24" focusable="false"><path d="M20 2H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3v3l4.5-3H20a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2Zm0 16h-9.1L9 19.3V18H4V4h16v14Zm-13-9h10v2H7V9Zm0 4h7v2H7v-2Z"/></svg>
  </span>
  <span>Chat with us</span>
</button>
<script src="{{ asset('js/revechat.js') }}?v={{ filemtime(public_path('js/revechat.js')) }}" defer></script>
