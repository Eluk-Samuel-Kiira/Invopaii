{{-- LEFT: sandbox widget floats on its own --}}
<aside class="widget widget--float" id="widget" data-open="false">
  <button type="button" id="widget-toggle" aria-expanded="false" aria-controls="widget-body">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#5B4B85" stroke-width="2" aria-hidden="true">
      <rect x="6" y="2" width="12" height="20" rx="3"/>
      <path d="M11 18h2"/>
    </svg>
    <span>Try the sandbox</span>
    <i class="plus" aria-hidden="true">+</i>
  </button>
  <div class="body" id="widget-body">
    Use this test key to send your first charge in under a minute.
    <div class="key">
      <code id="key">sk_test_9Kd2…41aF</code>
      <button type="button" id="copy-key">Copy</button>
    </div>
  </div>
</aside>

{{-- RIGHT: scroll-top + WhatsApp stay together --}}
<div class="fab-stack">
  <button type="button" id="scroll-top" class="fab fab--top" aria-label="Back to top">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <line x1="12" y1="19" x2="12" y2="5"/>
      <polyline points="5 12 12 5 19 12"/>
    </svg>
  </button>

  <a
    href="https://wa.me/256754428612"
    target="_blank"
    rel="noopener noreferrer"
    class="fab fab--whatsapp"
    aria-label="Chat with us on WhatsApp"
    title="Chat with us on WhatsApp"
  >
    <svg viewBox="0 0 24 24" fill="#fff" aria-hidden="true">
      <path d="M12 2C6.48 2 2 6.48 2 12c0 1.85.5 3.58 1.36 5.06L2 22l5.09-1.33A9.94 9.94 0 0 0 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.62 0-3.14-.44-4.44-1.2l-.32-.19-3.02.79.8-2.95-.21-.31A7.94 7.94 0 0 1 4 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-8 8z"/>
      <path d="M16.62 13.9c-.25-.13-1.47-.72-1.7-.81-.23-.09-.4-.13-.57.13-.17.25-.65.81-.8.97-.15.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.24-.75-.67-1.25-1.5-1.4-1.75-.15-.25-.02-.38.11-.51.11-.11.25-.29.37-.44.12-.15.16-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.57-1.37-.78-1.88-.2-.49-.41-.42-.57-.43h-.49c-.17 0-.44.06-.67.31-.23.25-.87.85-.87 2.08 0 1.23.89 2.41 1.02 2.58.13.17 1.75 2.68 4.24 3.75.59.25 1.05.4 1.41.51.59.19 1.13.16 1.55.1.47-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.1-.23-.16-.48-.29z"/>
    </svg>
  </a>
</div>

<style>
  /* sandbox widget floats on its own, bottom-left */
  .widget--float{
    position:fixed;
    left:20px;
    bottom:20px;
    z-index:40;
    width:min(300px,calc(100vw - 40px));
    background:#fff;color:var(--ink);
    border-radius:14px;box-shadow:0 14px 40px rgba(12,4,32,.45);
    overflow:hidden;
  }
</style>