@extends('layouts.home.app')

@section('title', 'Stardena Pay — API Reference v1')
@section('description', 'Stardena Pay API reference — payments, refunds, customers, balance, webhooks.')

@section('content')

{{-- ─────────────── docs layout ─────────────── --}}
<section style="padding:0;">
  <div class="wrap" style="max-width:1240px;">
    <div class="api-layout">

      {{-- ═══ sidebar ═══ --}}
      <aside class="api-side" aria-label="API reference navigation">
        <div style="font-family:var(--display);font-weight:700;color:#fff;font-size:18px;letter-spacing:-.01em;">Stardena</div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:22px;">API Reference v1</div>

        <p class="api-nav-group">Getting started</p>
        <a href="#quickstart">Quickstart</a>
        <a href="#auth">Authentication</a>
        <a href="#idempotency">Idempotency</a>
        <a href="#errors">Errors</a>

        <p class="api-nav-group">Payments</p>
        <a href="#payment-object">The payment object</a>
        <a href="#create-payment">Create</a>
        <a href="#retrieve-payment">Retrieve</a>
        <a href="#list-payments">List</a>
        <a href="#attempts">Attempts</a>
        <a href="#cancel-payment">Cancel</a>

        <p class="api-nav-group">Refunds</p>
        <a href="#create-refund">Create refund</a>
        <a href="#retrieve-refund">Retrieve</a>

        <p class="api-nav-group">Customers</p>
        <a href="#create-customer">Create</a>
        <a href="#update-customer">Update</a>

        <p class="api-nav-group">Money</p>
        <a href="#balance">Balance</a>
        <a href="#payouts">Payouts</a>

        <p class="api-nav-group">Webhooks</p>
        <a href="{{ route('docs.webhooks') }}">Full reference →</a>
      </aside>

      {{-- ═══ content ═══ --}}
      <main class="api-main">

        <p class="eyebrow">API Reference</p>
        <h1 style="font-size:clamp(28px,3.4vw,38px);">Stardena API</h1>
        <p class="lede" style="max-width:60ch;">Accept payments, issue refunds, and receive events. All requests are JSON. All responses are JSON.</p>

        <div class="api-callout">
          <strong style="color:#fff;">Base URL</strong>
          <code>{{ url('/api/v1') }}</code>
        </div>

        {{-- ═══ quickstart ═══ --}}
        <h2 id="quickstart">Quickstart</h2>

        <h3>1. Get a key</h3>
        <p>Dashboard → <strong>Developers → API Keys → Generate Key</strong>. The plaintext is shown once.</p>

        <h3>2. Charge a customer</h3>
        <div class="api-code">
<pre><span class="c"># create a test charge</span>
curl -X POST {{ url('/api/v1/payments') }} \
  -H <span class="s">"Authorization: Bearer sk_test_xxxxxxxx"</span> \
  -H <span class="s">"Content-Type: application/json"</span> \
  -H <span class="s">"Idempotency-Key: order-12345-attempt-1"</span> \
  -d <span class="s">'{
    "amount": 50000,
    "currency": "UGX",
    "payment_method_type": "mobile_money",
    "customer_email": "customer@example.com",
    "customer_country": "UG",
    "reference": "ORDER-12345"
  }'</span></pre>
        </div>

        <h3>3. Read the response</h3>
        <div class="api-code">
<pre>{
  <span class="s">"id"</span>: <span class="s">"pay_a1b2c3d4e5f6g7h8"</span>,
  <span class="s">"status"</span>: <span class="s">"succeeded"</span>,
  <span class="s">"amount"</span>: <span class="n">50000</span>,
  <span class="s">"currency"</span>: <span class="s">"UGX"</span>,
  <span class="s">"created"</span>: <span class="s">"2026-09-23T00:43:50+00:00"</span>
}</pre>
        </div>

        {{-- ═══ auth ═══ --}}
        <h2 id="auth">Authentication</h2>
        <p>Every request carries a Bearer token.</p>
        <div class="api-code">
<pre>Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx</pre>
        </div>

        <table class="api-table">
          <thead><tr><th>Type</th><th>Prefix</th><th>Use</th></tr></thead>
          <tbody>
            <tr><td>Secret</td><td><code>sk_</code></td><td>Server-side. Full access.</td></tr>
            <tr><td>Restricted</td><td><code>rk_</code></td><td>Server-side, scoped.</td></tr>
            <tr><td>Publishable</td><td><code>pk_</code></td><td>Client-side. Read only.</td></tr>
          </tbody>
        </table>

        <p>Test and live keys are isolated. A test key never sees live money.</p>

        {{-- ═══ idempotency ═══ --}}
        <h2 id="idempotency">Idempotency</h2>
        <p>POST/PUT/PATCH/DELETE accept an <code>Idempotency-Key</code> header. Keys expire after 24 hours.</p>

        <table class="api-table">
          <thead><tr><th>Case</th><th>Result</th></tr></thead>
          <tbody>
            <tr><td>Same key + same body</td><td>Cached response · <code>Idempotency-Replayed: true</code></td></tr>
            <tr><td>Same key + different body</td><td><code>422 idempotency_body_mismatch</code></td></tr>
            <tr><td>Concurrent request</td><td><code>409 idempotency_in_progress</code></td></tr>
          </tbody>
        </table>

        {{-- ═══ errors ═══ --}}
        <h2 id="errors">Errors</h2>
        <div class="api-code">
<pre>{
  <span class="s">"error"</span>: {
    <span class="s">"type"</span>: <span class="s">"validation_error"</span>,
    <span class="s">"message"</span>: <span class="s">"The amount field must be at least 1."</span>,
    <span class="s">"code"</span>: <span class="s">"ValidationException"</span>
  },
  <span class="s">"request_id"</span>: <span class="s">"a1b2c3d4-…"</span>
}</pre>
        </div>

        <table class="api-table">
          <thead><tr><th>Type</th><th>HTTP</th></tr></thead>
          <tbody>
            <tr><td><code>authentication_error</code></td><td>401</td></tr>
            <tr><td><code>permission_error</code></td><td>403</td></tr>
            <tr><td><code>validation_error</code></td><td>422</td></tr>
            <tr><td><code>idempotency_error</code></td><td>422 / 409</td></tr>
            <tr><td><code>not_found_error</code></td><td>404</td></tr>
            <tr><td><code>rate_limit_error</code></td><td>429</td></tr>
            <tr><td><code>api_error</code></td><td>500</td></tr>
          </tbody>
        </table>

        <div class="api-callout warn">
          Every response includes <code>X-Request-Id</code>. Quote it in support tickets.
        </div>

        {{-- ═══ payment object ═══ --}}
        <h2 id="payment-object">The payment object</h2>
        <div class="api-code">
<pre>{
  <span class="s">"id"</span>: <span class="s">"pay_a1b2c3d4e5f6g7h8"</span>,
  <span class="s">"object"</span>: <span class="s">"payment"</span>,
  <span class="s">"status"</span>: <span class="s">"succeeded"</span>,
  <span class="s">"amount"</span>: <span class="n">50000</span>,
  <span class="s">"amount_captured"</span>: <span class="n">50000</span>,
  <span class="s">"amount_refunded"</span>: <span class="n">0</span>,
  <span class="s">"currency"</span>: <span class="s">"UGX"</span>,
  <span class="s">"payment_method_type"</span>: <span class="s">"mobile_money"</span>,
  <span class="s">"customer_email"</span>: <span class="s">"customer@example.com"</span>,
  <span class="s">"reference"</span>: <span class="s">"ORDER-12345"</span>,
  <span class="s">"metadata"</span>: {},
  <span class="s">"created"</span>: <span class="s">"2026-09-23T00:43:50+00:00"</span>
}</pre>
        </div>

        <p>Amounts are integers in minor units. UGX uses 0 decimals, USD uses 2. Never floats.</p>

        <table class="api-table">
          <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
          <tbody>
            <tr><td><code>requires_action</code></td><td>Customer must authorize (PIN, 3DS)</td></tr>
            <tr><td><code>processing</code></td><td>Sent to provider, awaiting result</td></tr>
            <tr><td><code>succeeded</code></td><td>Funds captured</td></tr>
            <tr><td><code>failed</code></td><td>Provider rejected</td></tr>
            <tr><td><code>partially_refunded</code></td><td>Some amount refunded</td></tr>
            <tr><td><code>refunded</code></td><td>Fully refunded</td></tr>
            <tr><td><code>cancelled</code></td><td>Cancelled before completion</td></tr>
          </tbody>
        </table>

        {{-- ═══ create payment ═══ --}}
        <h2 id="create-payment"><span class="api-method api-method--post">POST</span> <code>/payments</code></h2>
        <p>Create and process a payment. Returns the payment immediately.</p>

        <table class="api-table">
          <thead><tr><th>Field</th><th>Type</th><th>Required</th></tr></thead>
          <tbody>
            <tr><td><code>amount</code></td><td>integer</td><td>yes — minor units</td></tr>
            <tr><td><code>currency</code></td><td>string</td><td>yes — ISO 4217</td></tr>
            <tr><td><code>payment_method_type</code></td><td>string</td><td>yes</td></tr>
            <tr><td><code>customer_id</code></td><td>string</td><td>no</td></tr>
            <tr><td><code>customer_email</code></td><td>string</td><td>no</td></tr>
            <tr><td><code>customer_phone</code></td><td>string</td><td>no</td></tr>
            <tr><td><code>customer_country</code></td><td>string</td><td>no — ISO2</td></tr>
            <tr><td><code>reference</code></td><td>string</td><td>no — your internal id</td></tr>
            <tr><td><code>description</code></td><td>string</td><td>no</td></tr>
            <tr><td><code>return_url</code></td><td>string</td><td>no</td></tr>
            <tr><td><code>metadata</code></td><td>object</td><td>no</td></tr>
          </tbody>
        </table>

        <p>Returns <code>200</code> on success, <code>202</code> if the payment needs action, <code>402</code> if the provider rejected it.</p>

        {{-- ═══ retrieve ═══ --}}
        <h2 id="retrieve-payment"><span class="api-method api-method--get">GET</span> <code>/payments/{id}</code></h2>
        <p>Accepts the <code>pay_…</code> public id or the numeric id. Returns extended fields: <code>provider</code>, <code>fee_amount</code>, <code>net_amount</code>, <code>next_action</code>.</p>

        {{-- ═══ list ═══ --}}
        <h2 id="list-payments"><span class="api-method api-method--get">GET</span> <code>/payments?limit=25</code></h2>
        <p>Most recent payments, newest first.</p>

        {{-- ═══ attempts ═══ --}}
        <h2 id="attempts"><span class="api-method api-method--get">GET</span> <code>/payments/{id}/attempts</code></h2>
        <p>Every call made to a provider. Useful when a payment fails and you need to see why.</p>

        {{-- ═══ cancel ═══ --}}
        <h2 id="cancel-payment"><span class="api-method api-method--post">POST</span> <code>/payments/{id}/cancel</code></h2>
        <p>Cancels a payment in <code>requires_*</code> or <code>processing</code> state.</p>

        {{-- ═══ refunds ═══ --}}
        <h2 id="create-refund"><span class="api-method api-method--post">POST</span> <code>/refunds</code></h2>
        <p>Full or partial refund. Returns <code>201</code> with the refund object.</p>

        <div class="api-code">
<pre>{
  <span class="s">"payment_id"</span>: <span class="s">"pay_a1b2c3d4e5f6g7h8"</span>,
  <span class="s">"amount"</span>: <span class="n">25000</span>,               <span class="c">// omit for full refund</span>
  <span class="s">"reason"</span>: <span class="s">"requested_by_customer"</span>
}</pre>
        </div>

        <table class="api-table">
          <thead><tr><th>Field</th><th>Type</th><th>Required</th></tr></thead>
          <tbody>
            <tr><td><code>payment_id</code></td><td>string</td><td>yes</td></tr>
            <tr><td><code>amount</code></td><td>integer</td><td>no — omit for full</td></tr>
            <tr><td><code>reason</code></td><td>string</td><td>no — <code>requested_by_customer</code>, <code>duplicate</code>, <code>fraudulent</code></td></tr>
            <tr><td><code>description</code></td><td>string</td><td>no</td></tr>
          </tbody>
        </table>

        <h2 id="retrieve-refund"><span class="api-method api-method--get">GET</span> <code>/refunds/{id}</code></h2>
        <p>Retrieve a single refund, or <code>GET /refunds</code> to list them.</p>

        {{-- ═══ customers ═══ --}}
        <h2 id="create-customer"><span class="api-method api-method--post">POST</span> <code>/customers</code></h2>

        <div class="api-code">
<pre>{
  <span class="s">"name"</span>: <span class="s">"Amina Nakato"</span>,
  <span class="s">"email"</span>: <span class="s">"amina@example.com"</span>,
  <span class="s">"phone"</span>: <span class="s">"+256772100001"</span>,
  <span class="s">"country_code"</span>: <span class="s">"UG"</span>
}</pre>
        </div>

        <h2 id="update-customer"><span class="api-method api-method--put">PUT</span> <code>/customers/{id}</code></h2>
        <p>Update a customer. Same body shape as create.</p>

        {{-- ═══ balance ═══ --}}
        <h2 id="balance"><span class="api-method api-method--get">GET</span> <code>/balance</code></h2>

        <div class="api-code">
<pre>{
  <span class="s">"object"</span>: <span class="s">"balance"</span>,
  <span class="s">"data"</span>: [{
    <span class="s">"currency"</span>: <span class="s">"UGX"</span>,
    <span class="s">"available"</span>: <span class="n">47000</span>,     <span class="c">// withdrawable now</span>
    <span class="s">"pending"</span>: <span class="n">0</span>,         <span class="c">// captured, not matured</span>
    <span class="s">"reserved"</span>: <span class="n">0</span>,        <span class="c">// dispute holds / rolling reserve</span>
    <span class="s">"in_transit"</span>: <span class="n">0</span>,      <span class="c">// sent to bank, not confirmed</span>
    <span class="s">"total"</span>: <span class="n">47000</span>
  }]
}</pre>
        </div>

        <h2 id="payouts"><span class="api-method api-method--get">GET</span> <code>/payouts</code></h2>
        <p>List payouts. <code>GET /payouts/{id}</code> for a single one. Read-only via API; creation happens from the dashboard or the daily schedule.</p>

        {{-- ═══ closing ═══ --}}
        <div class="api-callout" style="margin-top:56px;">
          <strong style="color:#fff;">Need the webhook reference?</strong><br>
          <a href="{{ route('docs.webhooks') }}" style="color:var(--accent-soft);">Signature verification, event types, retries →</a>
        </div>

      </main>
    </div>
  </div>
</section>

@endsection

@push('styles')
<style>
/* ─────────────────────────────────────────────
   API reference layout — dense, info-first
   Borrows the site palette, adds only what's
   needed for a two-column docs page.
   ───────────────────────────────────────────── */

.api-layout{
  display:grid;
  grid-template-columns:220px minmax(0,1fr);
  gap:56px;
  padding:56px 0 120px;
  align-items:start;
}

/* sidebar */
.api-side{
  position:sticky;top:20px;
  align-self:start;
  font-size:14px;
  line-height:1.4;
  max-height:calc(100vh - 40px);
  overflow-y:auto;
  padding-right:8px;
}
.api-side::-webkit-scrollbar{width:4px}
.api-side::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:4px}

.api-nav-group{
  font-size:11.5px;
  font-weight:600;
  letter-spacing:.08em;
  text-transform:uppercase;
  color:var(--accent-soft);
  margin:22px 0 6px;
}
.api-nav-group:first-of-type{margin-top:0}

.api-side a{
  display:block;
  padding:5px 0;
  color:var(--muted);
  text-decoration:none;
  font-size:14px;
  transition:color .1s ease;
}
.api-side a:hover{color:#fff}

/* main */
.api-main{min-width:0;font-size:15.5px;line-height:1.7;color:#fff}
.api-main p{color:var(--muted);margin:12px 0}

.api-main h2{
  font-family:var(--display);
  font-size:22px;
  font-weight:700;
  letter-spacing:-.01em;
  margin:56px 0 14px;
  padding-top:24px;
  border-top:1px solid var(--line);
  color:#fff;
  scroll-margin-top:24px;
}
.api-main h2:first-of-type{border-top:0;padding-top:0;margin-top:24px}

.api-main h3{
  font-family:var(--display);
  font-size:16.5px;
  font-weight:600;
  margin:28px 0 8px;
  color:#fff;
}

.api-main code{
  font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
  font-size:.9em;
  background:rgba(255,255,255,.08);
  padding:2px 6px;
  border-radius:5px;
  color:#E7DDFF;
}

.api-main a{color:var(--accent-soft)}
.api-main a:hover{color:#fff}

/* callout */
.api-callout{
  padding:14px 18px;
  border-left:3px solid var(--violet);
  background:rgba(110,63,231,.12);
  border-radius:0 8px 8px 0;
  margin:20px 0;
  font-size:14.5px;
  color:var(--muted);
}
.api-callout strong{color:#fff}
.api-callout.warn{
  border-left-color:#f59e0b;
  background:rgba(245,158,11,.1);
}

/* code block — matches .code on landing, tighter */
.api-code{
  background:#160C2B;
  border:1px solid rgba(255,255,255,.08);
  border-radius:12px;
  overflow:hidden;
  margin:16px 0;
}
.api-code pre{
  margin:0;
  padding:18px 20px;
  font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
  font-size:13px;
  line-height:1.65;
  overflow-x:auto;
  color:#D9CDF5;
}
.api-code .k{color:#FF9E64}
.api-code .s{color:#9BE7A8}
.api-code .c{color:#8574B5;font-style:italic}
.api-code .n{color:#7FD3FF}

/* table */
.api-table{
  width:100%;
  border-collapse:collapse;
  margin:18px 0;
  font-size:14px;
}
.api-table th,
.api-table td{
  padding:10px 14px;
  text-align:left;
  border-bottom:1px solid var(--line);
  vertical-align:top;
}
.api-table th{
  font-weight:600;
  color:var(--accent-soft);
  font-size:12px;
  letter-spacing:.06em;
  text-transform:uppercase;
  background:rgba(255,255,255,.02);
}
.api-table td{color:var(--muted)}
.api-table td code{color:#E7DDFF;background:rgba(110,63,231,.18)}

/* method pill */
.api-method{
  display:inline-block;
  font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
  font-size:11.5px;
  font-weight:700;
  letter-spacing:.03em;
  padding:3px 8px;
  border-radius:5px;
  margin-right:10px;
  vertical-align:middle;
}
.api-method--post{background:#0E3D24;color:#7EE3A8}
.api-method--get {background:#0A2D46;color:#7FD3FF}
.api-method--put {background:#3D2E0E;color:#FFD37F}
.api-method--delete{background:#3D0E1A;color:#FF9EB5}

/* responsive */
@media (max-width:900px){
  .api-layout{grid-template-columns:1fr;gap:28px;padding:32px 0 80px}
  .api-side{
    position:static;max-height:none;
    border:1px solid var(--line);border-radius:14px;
    padding:20px;
    background:rgba(255,255,255,.02);
  }
  .api-main h2{font-size:19px;margin:40px 0 12px;padding-top:20px}
  .api-table{font-size:13px}
  .api-table th,.api-table td{padding:8px 10px}
}
</style>
@endpush