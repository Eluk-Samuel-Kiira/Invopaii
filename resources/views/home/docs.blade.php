@extends('layouts.home.app')

@section('title', 'Stardena Pay — API documentation')
@section('description', 'Reference and guides for the Stardena Pay API — cards, mobile money, payment links, SMS, and webhooks.')

@section('content')

<section class="docs-hero">
  <div class="wrap">
    <p class="eyebrow">Developers</p>
    <h1>Build with Stardena Pay</h1>
    <p class="lede">Everything you need to charge cards, collect mobile money, and get notified the moment it settles — by webhook or by text.</p>
  </div>
</section>

<section class="docs-body">
  <div class="wrap docs-grid">
    <a class="doc-card" href="#">
      <h3>Quickstart</h3>
      <p>Create your first test charge in under five minutes.</p>
    </a>
    <a class="doc-card" href="#">
      <h3>Cards</h3>
      <p>Accept Visa, Mastercard and Amex from anywhere in the world.</p>
    </a>
    <a class="doc-card" href="#">
      <h3>Mobile money</h3>
      <p>Collect MTN MoMo, Airtel Money, M-Pesa and more.</p>
    </a>
    <a class="doc-card" href="#">
      <h3>Payment links</h3>
      <p>Charge without writing any code at all.</p>
    </a>
    <a class="doc-card" href="#">
      <h3>Webhooks</h3>
      <p>Signed events for every charge, refund and payout.</p>
    </a>
    <a class="doc-card" href="#">
      <h3>SMS</h3>
      <p>Send OTPs and payment alerts by text, no app required.</p>
    </a>
  </div>
</section>

@endsection