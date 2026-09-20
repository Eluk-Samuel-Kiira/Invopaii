<footer>
  <div class="wrap">
    <div class="foot-top">
      <div>
        <p class="legal">Stardena Pay is a payments technology provider. Card processing and settlement are handled by licensed partner institutions in each market. © {{ date('Y') }} Stardena.</p>
        <nav class="foot-links">
          <a href="{{ url('/docs') }}">Documentation</a>
          <a href="{{ url('/status') }}">Status</a>
          <a href="{{ url('/privacy') }}">Privacy</a>
          <a href="{{ url('/terms') }}">Terms</a>
        </nav>
      </div>
      <div class="foot-brand">
        <img class="mark mark--lg" src="{{ asset('pay.png') }}" width="38" height="38" alt="">
        <span class="brand-word">Stardena Pay</span>
      </div>
    </div>

    <hr class="rule">

    <div class="foot-bottom">
      <div>
        <h3>Money, moved simply</h3>
        <span>Cards worldwide. Mobile money across Africa. One dashboard.</span>
      </div>
      <a class="btn" href="{{ url('/contact-sales') }}">Talk to sales</a>
    </div>
  </div>
</footer>