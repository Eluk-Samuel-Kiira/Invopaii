<header class="nav" id="site-nav">
  <div class="wrap nav-inner">
    <a class="brand" href="{{ url('/') }}">
      <img class="mark" src="{{ asset('pay.png') }}" width="28" height="28" alt="">
      <span class="brand-word">Stardena Pay</span>
    </a>

    <nav class="nav-links" aria-label="Primary">
      <a href="{{ url('/#methods') }}">Payment methods</a>
      <a href="{{ url('/docs') }}">
        <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="8 6 2 12 8 18"/>
          <polyline points="16 6 22 12 16 18"/>
        </svg>
        Developers
      </a>
      <a href="{{ url('/#pricing') }}">Pricing</a>
    </nav>

    <div class="nav-actions">
      <a class="btn btn--light" href="{{ url('/login') }}" target="_blank">Sign in</a>
      <a class="btn" href="{{ url('/#signup') }}">Get started</a>
    </div>

    <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="mobile-menu" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>
  </div>

  <nav class="mobile-menu" id="mobile-menu" data-open="false" aria-label="Mobile">
    <a href="{{ url('/#methods') }}">Payment methods</a>
    <a href="{{ url('/docs') }}">Developers</a>
    <a href="{{ url('/#pricing') }}">Pricing</a>
    <hr>
    <a class="btn btn--light" href="{{ url('/login') }}" target="_blank">Sign in</a>
    <a class="btn" href="{{ url('/#signup') }}">Get started</a>
  </nav>
</header>