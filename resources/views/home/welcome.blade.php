@extends('layouts.home.app')

@section('title', 'Stardena Pay — Get paid, everywhere')
@section('description', 'Collect cards from anywhere in the world and mobile money across Africa through one API, payment links, and SMS alerts.')

@section('content')

<!-- ─────────────── hero ─────────────── -->
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <p class="eyebrow">Payments infrastructure · Live in 14 countries</p>
      <h1>Get paid. Anywhere you sell.</h1>
      <p class="lede">Cards from anywhere in the world. Mobile money across Africa. One API, one dashboard, one payout.</p>
      <div class="cta-row">
        <a class="btn" href="#signup">Start collecting</a>
        <a class="btn btn--ghost" href="#docs">Read the docs</a>
      </div>
    </div>

    <div class="stage">
      <div class="chip">Paid · 0.8s</div>
      <div class="paycard">
        <div class="row">
          <span class="co">Northwind Studio</span>
          <span class="co">#INV-1042</span>
        </div>
        <div class="amount">$2,480.00</div>
        <div class="sub">Due today · pay by card or mobile money</div>
        <div class="field"><span>4242 4242 4242 4242</span><span>12/28</span></div>
        <div class="paybtn">Pay $2,480.00</div>
      </div>
      <div class="momo-toast">
        <span class="dot-pulse" aria-hidden="true"></span>
        <div>
          <strong>MTN MoMo</strong>
          <span>Approve on your phone</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ─────────────── payment methods ─────────────── -->
<section class="methods" id="methods">
  <div class="wrap">
    <p class="eyebrow">Coverage</p>
    <h2>Local rails, everywhere you sell</h2>
    <p class="lede">One integration reaches a card in London and a phone in Lagos.</p>

    <div class="method-grid">
      <div class="method-col">
        <h3>Cards, worldwide</h3>
        <p>Accept any major card, from any country, in the currency your customer already uses.</p>
        <div class="badge-row">
          <span class="badge">Visa</span>
          <span class="badge">Mastercard</span>
          <span class="badge">American Express</span>
          <span class="badge">Verve</span>
        </div>
      </div>

      <div class="method-col">
        <h3>Mobile money, across Africa</h3>
        <p>Customers pay straight from their phone — no card, no bank account needed.</p>
        <div class="badge-row">
          <span class="badge">MTN MoMo</span>
          <span class="badge">Airtel Money</span>
          <span class="badge">M-Pesa</span>
          <span class="badge">Orange Money</span>
          <span class="badge">Tigo Pesa</span>
        </div>
        <p class="flag-row">🇺🇬 🇰🇪 🇳🇬 🇬🇭 🇿🇦 🇹🇿 🇷🇼 🇨🇮</p>
      </div>
    </div>
  </div>
</section>

<!-- ─────────────── developers split ─────────────── -->
<section class="split" id="docs">
  <div class="wrap split-grid">
    <div class="code">
      <header>
        <i class="dot"></i><i class="dot"></i><i class="dot"></i>
        <span class="tab">charge.js</span>
      </header>
<pre><span class="c">// one call, any payment method</span>
<span class="k">const</span> charge = <span class="k">await</span> stardena.charges.<span class="n">create</span>({
  amount: <span class="n">248000</span>,
  currency: <span class="s">"usd"</span>,
  method: <span class="s">"auto"</span>,        <span class="c">// card, momo — Stardena picks the rail</span>
  customer: <span class="s">"cus_9Kd2"</span>
});

<span class="c">// webhook fires the moment it settles</span>
charge.status; <span class="c">// → "succeeded"</span></pre>
    </div>

    <div>
      <p class="eyebrow">Built for developers</p>
      <h2>One API. Every method.</h2>
      <p class="lede">Test keys work the second you sign up. Webhooks retry on their own, and every event is replayable from the dashboard.</p>
      <a class="btn" href="#signup">Get your test keys</a>
    </div>
  </div>
</section>

<!-- ─────────────── feature cards ─────────────── -->
<section class="features" id="features">
  <div class="wrap">
    <h2>Everything you need to collect</h2>
    <p>Four ways to take money and stay on top of it, all in the same dashboard.</p>

    <div class="cards">
      <article class="card">
        <div class="art">
          <svg width="80" height="80" viewBox="0 0 96 96" aria-hidden="true">
            <defs>
              <linearGradient id="c1" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#8A5BFF"/>
                <stop offset="1" stop-color="#E05FD0"/>
              </linearGradient>
            </defs>
            <rect x="12" y="24" width="72" height="48" rx="10" fill="url(#c1)"/>
            <rect x="12" y="38" width="72" height="9" fill="#2E1A5C" opacity=".35"/>
            <rect x="22" y="56" width="26" height="6" rx="3" fill="#fff" opacity=".85"/>
          </svg>
        </div>
        <span class="tag">Cards</span>
        <h3>Take cards worldwide</h3>
        <p>A hosted checkout that works in local currencies and passes 3DS on its own.</p>
      </article>

      <article class="card">
        <div class="art">
          <svg width="80" height="80" viewBox="0 0 96 96" aria-hidden="true">
            <defs>
              <linearGradient id="c4" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#3FE7B0"/>
                <stop offset="1" stop-color="#6ED0FF"/>
              </linearGradient>
            </defs>
            <rect x="30" y="14" width="36" height="68" rx="8" fill="url(#c4)"/>
            <rect x="38" y="24" width="20" height="38" rx="3" fill="#fff" opacity=".9"/>
            <circle cx="48" cy="72" r="3.5" fill="#fff" opacity=".9"/>
          </svg>
        </div>
        <span class="tag">Mobile money</span>
        <h3>Collect across Africa</h3>
        <p>MTN, Airtel, M-Pesa and more — money moves straight off the customer's phone.</p>
      </article>

      <article class="card">
        <div class="art">
          <svg width="80" height="80" viewBox="0 0 96 96" aria-hidden="true">
            <defs>
              <linearGradient id="c2" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#FF9A3C"/>
                <stop offset="1" stop-color="#F2527B"/>
              </linearGradient>
            </defs>
            <path d="M20 66c14 0 14-36 28-36s14 36 28 36" fill="none" stroke="url(#c2)" stroke-width="9" stroke-linecap="round"/>
            <circle cx="20" cy="66" r="8" fill="#8A5BFF"/>
            <circle cx="76" cy="66" r="8" fill="#E05FD0"/>
          </svg>
        </div>
        <span class="tag">SMS + webhooks</span>
        <h3>Know the second it lands</h3>
        <p>A signed webhook for every charge, plus a text to your phone when you're away from a screen.</p>
      </article>

      <article class="card">
        <div class="art">
          <svg width="80" height="80" viewBox="0 0 96 96" aria-hidden="true">
            <defs>
              <linearGradient id="c3" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#6ED0FF"/>
                <stop offset="1" stop-color="#7C4DFF"/>
              </linearGradient>
            </defs>
            <rect x="24" y="16" width="48" height="64" rx="8" fill="url(#c3)"/>
            <rect x="34" y="30" width="28" height="5" rx="2.5" fill="#fff" opacity=".9"/>
            <rect x="34" y="42" width="20" height="5" rx="2.5" fill="#fff" opacity=".65"/>
            <rect x="34" y="58" width="28" height="10" rx="5" fill="#fff" opacity=".9"/>
          </svg>
        </div>
        <span class="tag">Payment links</span>
        <h3>Charge without code</h3>
        <p>Create a link, send it anywhere, watch it get paid. No integration needed.</p>
      </article>
    </div>

    <a class="btn" href="#signup">See all features</a>
  </div>
</section>

<!-- ─────────────── invoicing panel ─────────────── -->
<section class="panel-sec">
  <div class="wrap">
    <div class="panel">
      <div class="invoice">
        <div class="head"><span>Invoice INV-1042</span><span>Due 16 Sep</span></div>
        <div class="total">$2,480.00</div>
        <div class="line"><span>Design retainer</span><span>$1,800.00</span></div>
        <div class="line"><span>Motion pass</span><span>$520.00</span></div>
        <div class="line"><span>VAT</span><span>$160.00</span></div>
        <div class="send">Send invoice</div>
      </div>
      <div>
        <p class="eyebrow">Invoicing</p>
        <h2>Invoices that chase themselves</h2>
        <p class="lede">Bill in your customer's currency, send reminders by email or SMS, and settle straight into your local account.</p>
        <a class="btn" href="#signup">Send your first invoice</a>
      </div>
    </div>
  </div>
</section>

<div class="mid-cta" id="pricing">
  <a class="btn btn--ghost" href="#docs">See pricing</a>
</div>

<div class="shelf-b"></div>

<!-- ─────────────── signup ─────────────── -->
<section class="signup" id="signup">
  <div class="wrap">
    <h2>Start taking payments today</h2>
    <p>Create an account, grab your test keys, and go live once you're ready.</p>
    <form class="form" id="signup-form" novalidate>
      <label for="email">Work email</label>
      <input id="email" name="email" type="email" placeholder="you@company.com" autocomplete="email" required>
      <button class="btn" type="submit">Create account</button>
      <p class="form-msg" id="form-msg" role="status"></p>
    </form>
  </div>
</section>

@endsection