@extends('layouts.home.app')

@section('title', 'Stardena Pay — Webhook delivery architecture')
@section('description', 'How Stardena fires outbound webhooks — the full pipeline, retries, signing, and debugging.')

@section('content')

<section style="padding:0;">
  <div class="wrap" style="max-width:1240px;">
    <div class="api-layout">

      {{-- ═══ sidebar ═══ --}}
      <aside class="api-side" aria-label="Architecture navigation">
        <div style="font-family:var(--display);font-weight:700;color:#fff;font-size:18px;letter-spacing:-.01em;">Stardena</div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:22px;">Architecture</div>

        <p class="api-nav-group">Pipeline</p>
        <a href="#pipeline">Full pipeline</a>
        <a href="#endpoint">Endpoint registration</a>
        <a href="#payment">Payment creation</a>
        <a href="#handle-success">handleSuccess chain</a>
        <a href="#dispatcher">EventDispatcher</a>
        <a href="#delivery">DeliverWebhook job</a>

        <p class="api-nav-group">Reference</p>
        <a href="#payload">Event payload</a>
        <a href="#retries">Retry policy</a>
        <a href="#signature">Signature verification</a>
        <a href="#debug">Debugging a missing webhook</a>
        <a href="#files">Files &amp; responsibilities</a>
        <a href="#rules">Non-negotiables</a>

        <p class="api-nav-group">Related</p>
        <a href="{{ route('docs.api') }}">← API reference</a>
        <a href="{{ route('docs.webhooks') }}">← Webhook reference</a>
      </aside>

      {{-- ═══ content ═══ --}}
      <main class="api-main">

        <p class="eyebrow">Architecture</p>
        <h1 style="font-size:clamp(28px,3.4vw,38px);">Webhook delivery</h1>
        <p class="lede" style="max-width:60ch;">How Stardena fires outbound webhooks — the full pipeline, retries, signing, and debugging. Read this before touching any webhook-related file.</p>

        <div class="api-callout">
          <strong>Applies to:</strong> payments, refunds, payouts, disputes, and customer events.
        </div>

        {{-- ═══ pipeline ═══ --}}
        <h2 id="pipeline">Full pipeline</h2>

        <div class="api-code">
<pre><span class="c">1.</span>  Merchant action (API, dashboard, subscription job)
<span class="c">↓</span>
<span class="c">2.</span>  PaymentIntentService::create()       → creates payments row
<span class="c">↓</span>
<span class="c">3.</span>  PaymentProcessor::process()          → routes to provider, creates payment_attempts
<span class="c">↓</span>
<span class="c">4.</span>  Provider charges                     → returns ProviderResult
<span class="c">↓</span>
<span class="c">5.</span>  PaymentProcessor::handleSuccess()    → runs all post-payment side effects
<span class="c">↓</span>
<span class="c">6.</span>  FeeService::applyProcessingFee()     → writes applied_fees
<span class="c">↓</span>
<span class="c">7.</span>  LedgerService::post()                → writes ledger_transactions + ledger_entries
<span class="c">↓</span>
<span class="c">8.</span>  creditMerchantBalance()              → updates balances, writes balance_transactions
<span class="c">↓</span>
<span class="c">9.</span>  AuditLog::record()                   → writes audit_logs
<span class="c">↓</span>
<span class="c">10.</span> notifyPaymentSucceeded()            → writes notifications
<span class="c">↓</span>
<span class="c">11.</span> EventDispatcher::dispatch()         → writes events, fans out webhook_deliveries
<span class="c">↓</span>
<span class="c">12.</span> DeliverWebhook job (queued)         → signs payload, POSTs to endpoint URL
<span class="c">↓</span>
<span class="c">13.</span> Merchant server receives it         → verifies signature, updates their system</pre>
        </div>

        {{-- ═══ endpoint ═══ --}}
        <h2 id="endpoint">Endpoint registration</h2>
        <p>A webhook endpoint lives in <code>webhook_endpoints</code>:</p>

        <div class="api-code">
<pre>$endpoint = $company->webhookEndpoints()->create([
    <span class="s">'mode'</span> => <span class="s">'test'</span>,                                  <span class="c">// or 'live'</span>
    <span class="s">'url'</span> => <span class="s">'https://merchant.example.com/webhooks'</span>,
    <span class="s">'description'</span> => <span class="s">'Production webhook'</span>,
    <span class="s">'enabled_events'</span> => [<span class="s">'*'</span>],                         <span class="c">// or explicit list</span>
    <span class="s">'status'</span> => <span class="s">'enabled'</span>,
    <span class="s">'timeout_seconds'</span> => <span class="n">10</span>,
    <span class="s">'max_attempts'</span> => <span class="n">8</span>,
]);</pre>
        </div>

        <p>The model auto-generates a <code>whsec_…</code> signing secret on create. It's stored <strong>encrypted</strong> in the <code>secret</code> column.</p>

        <table class="api-table">
          <thead><tr><th>Accessor</th><th>Returns</th></tr></thead>
          <tbody>
            <tr><td><code>$endpoint-&gt;secret</code></td><td>Full plaintext secret — used for signing</td></tr>
            <tr><td><code>$endpoint-&gt;masked_secret</code></td><td>What the dashboard shows: <code>whsec_••••••••AQY</code></td></tr>
          </tbody>
        </table>

        <div class="api-callout warn">
          The plaintext secret must never leave the server, never appear in an API response, never be logged.
        </div>

        {{-- ═══ payment ═══ --}}
        <h2 id="payment">Payment creation</h2>

        <div class="api-code">
<pre>$payment = PaymentIntentService::createAndProcess($company, [
    <span class="s">'mode'</span> => <span class="s">'test'</span>,
    <span class="s">'source'</span> => <span class="s">'api'</span>,
    <span class="s">'customer_id'</span> => $customer->id,
    <span class="s">'currency'</span> => <span class="s">'UGX'</span>,
    <span class="s">'amount'</span> => <span class="n">25000</span>,
    <span class="s">'payment_method_type'</span> => <span class="s">'mobile_money'</span>,
    <span class="s">'customer_country'</span> => <span class="s">'UG'</span>,
    <span class="s">'reference'</span> => <span class="s">'ORDER-12345'</span>,
]);</pre>
        </div>

        <p><code>createAndProcess()</code> resolves a provider via <code>ProviderRouter::route([...])</code>, inserts a <code>payments</code> row, then calls <code>PaymentProcessor::process()</code> which routes and invokes the provider's <code>charge()</code>.</p>

        {{-- ═══ handleSuccess ═══ --}}
        <h2 id="handle-success">handleSuccess chain</h2>
        <p>Inside one <code>DB::transaction()</code>:</p>

        <div class="api-code">
<pre>$payment = PaymentStateMachine::transition($payment, <span class="s">'succeeded'</span>, [
    <span class="s">'provider_reference'</span> => $result->providerReference,
    <span class="s">'amount_captured'</span> => $payment->amount,
    <span class="s">'captured_at'</span> => now(),
]);

$appliedFee = FeeService::applyProcessingFee($payment);
$feeTotal = $appliedFee?->total_amount ?? <span class="n">0</span>;

self::postPaymentLedger($payment, $feeTotal);
self::creditMerchantBalance($payment, $feeTotal);

\App\Models\Platform\AuditLog::record([<span class="s">'action'</span> => <span class="s">'payment.succeeded'</span>, ...]);

self::notifyPaymentSucceeded($payment, $feeTotal);

<span class="c">// 👇 The webhook trigger</span>
\App\Services\Webhook\EventDispatcher::dispatch(
    company: $payment->company,
    type: <span class="s">'payment.succeeded'</span>,
    data: self::paymentEventPayload($payment, $feeTotal),
    resource: $payment,
    origin: <span class="s">'system'</span>,
    mode: $payment->mode,
);</pre>
        </div>

        <p>Everything inside the transaction is atomic. If any step fails, the payment fails and rolls back. Webhook dispatch is one of the last steps.</p>

        {{-- ═══ dispatcher ═══ --}}
        <h2 id="dispatcher">EventDispatcher</h2>

        <h3>A. Records the event</h3>
        <div class="api-code">
<pre>$event = Event::create([
    <span class="s">'company_id'</span> => $company->id,
    <span class="s">'mode'</span> => $effectiveMode,
    <span class="s">'type'</span> => <span class="s">'payment.succeeded'</span>,
    <span class="s">'resource_type'</span> => <span class="s">'App\Models\Payment\Payment'</span>,
    <span class="s">'resource_id'</span> => $payment->id,
    <span class="s">'resource_public_id'</span> => $payment->public_id,
    <span class="s">'data'</span> => [...],
    <span class="s">'api_version'</span> => <span class="s">'2025-01-01'</span>,
    <span class="s">'origin'</span> => <span class="s">'system'</span>,
    <span class="s">'pending_webhooks'</span> => <span class="n">0</span>,
]);</pre>
        </div>

        <h3>B. Finds subscribed endpoints</h3>
        <div class="api-code">
<pre>$endpoints = WebhookEndpoint::where(<span class="s">'company_id'</span>, $company->id)
    -&gt;where(<span class="s">'mode'</span>, $event-&gt;mode)
    -&gt;where(<span class="s">'status'</span>, <span class="s">'enabled'</span>)
    -&gt;get()
    -&gt;filter(fn ($e) =&gt; $e-&gt;isSubscribedTo(<span class="s">'payment.succeeded'</span>));</pre>
        </div>

        <h3>C. Creates deliveries and queues the job</h3>
        <div class="api-code">
<pre>$delivery = WebhookDelivery::create([
    <span class="s">'webhook_endpoint_id'</span> => $endpoint-&gt;id,
    <span class="s">'event_id'</span> => $event-&gt;id,
    <span class="s">'company_id'</span> => $company-&gt;id,
    <span class="s">'mode'</span> => $event-&gt;mode,
    <span class="s">'event_type'</span> => <span class="s">'payment.succeeded'</span>,
    <span class="s">'status'</span> => <span class="s">'pending'</span>,
    <span class="s">'attempt'</span> => <span class="n">0</span>,
    <span class="s">'scheduled_for'</span> => now(),
    <span class="s">'next_retry_at'</span> => now(),
]);

\App\Jobs\DeliverWebhook::dispatch($delivery);</pre>
        </div>

        {{-- ═══ delivery ═══ --}}
        <h2 id="delivery">DeliverWebhook job</h2>
        <p>The job:</p>
        <ol style="color:var(--muted);line-height:1.9;">
          <li>Reloads the delivery with <code>endpoint</code> and <code>event</code></li>
          <li>Bumps <code>attempt</code> from N to N+1</li>
          <li>Builds the JSON body via <code>$event-&gt;toWebhookPayload()</code></li>
          <li>Signs it:</li>
        </ol>

        <div class="api-code">
<pre>$timestamp = time();
$signature = $endpoint-&gt;signPayload($body, $timestamp);

<span class="c">// WebhookEndpoint::signPayload</span>
<span class="k">public function</span> <span class="n">signPayload</span>(<span class="k">string</span> $body, <span class="k">int</span> $timestamp): <span class="k">string</span>
{
    $signed = $timestamp . <span class="s">'.'</span> . $body;
    $hex = hash_hmac(<span class="s">'sha256'</span>, $signed, $this-&gt;secret);
    <span class="k">return</span> <span class="s">"t={$timestamp},v1={$hex}"</span>;
}</pre>
        </div>

        <p>Then POSTs with these headers:</p>

        <div class="api-code">
<pre>Content-Type: application/json
User-Agent: Stardena-Webhooks/1.0
Stardena-Event-Id: evt_...
Stardena-Event-Type: payment.succeeded
Stardena-Signature: t=...,v1=...
Stardena-Delivery-Id: &lt;uuid&gt;
Stardena-Attempt: 1</pre>
        </div>

        {{-- ═══ payload ═══ --}}
        <h2 id="payload">Event payload</h2>
        <div class="api-code">
<pre>{
  <span class="s">"id"</span>: <span class="s">"evt_ifhg3hfrgseenlz8zpivayjn"</span>,
  <span class="s">"type"</span>: <span class="s">"payment.succeeded"</span>,
  <span class="s">"api_version"</span>: <span class="s">"2025-01-01"</span>,
  <span class="s">"created"</span>: <span class="s">"2026-09-23T04:50:07+00:00"</span>,
  <span class="s">"data"</span>: {
    <span class="s">"object"</span>: { ...payment fields... },
    <span class="s">"previous_attributes"</span>: <span class="k">null</span>
  },
  <span class="s">"resource"</span>: {
    <span class="s">"type"</span>: <span class="s">"App\\Models\\Payment\\Payment"</span>,
    <span class="s">"id"</span>: <span class="s">"pay_pldnxnq1clre5f3mnun4nrih"</span>
  }
}</pre>
        </div>

        <table class="api-table">
          <thead><tr><th>Field</th><th>Origin</th></tr></thead>
          <tbody>
            <tr><td><code>id</code></td><td><code>events.public_id</code></td></tr>
            <tr><td><code>type</code></td><td><code>events.type</code></td></tr>
            <tr><td><code>api_version</code></td><td><code>events.api_version</code></td></tr>
            <tr><td><code>created</code></td><td><code>events.created_at</code> (ISO8601)</td></tr>
            <tr><td><code>data.object</code></td><td>Controller-specific payload builder</td></tr>
            <tr><td><code>data.previous_attributes</code></td><td><code>events.previous_attributes</code></td></tr>
            <tr><td><code>resource.type</code></td><td><code>events.resource_type</code> (FQCN)</td></tr>
            <tr><td><code>resource.id</code></td><td><code>events.resource_public_id</code></td></tr>
          </tbody>
        </table>

        {{-- ═══ retries ═══ --}}
        <h2 id="retries">Retry policy</h2>
        <p>Failed deliveries retry with exponential backoff.</p>

        <table class="api-table">
          <thead><tr><th>Attempt</th><th>Delay</th></tr></thead>
          <tbody>
            <tr><td>1</td><td>immediate</td></tr>
            <tr><td>2</td><td>30 seconds</td></tr>
            <tr><td>3</td><td>5 minutes</td></tr>
            <tr><td>4</td><td>30 minutes</td></tr>
            <tr><td>5</td><td>2 hours</td></tr>
            <tr><td>6</td><td>6 hours</td></tr>
            <tr><td>7</td><td>12 hours</td></tr>
            <tr><td>8</td><td>24 hours</td></tr>
          </tbody>
        </table>

        <p>After the 8th attempt: <code>abandoned</code>. Operators can retry manually from the dashboard.</p>

        <div class="api-callout warn">
          <strong>Auto-disable:</strong> endpoints failing <strong>20 consecutive</strong> deliveries are set to <code>auto_disabled</code>. Re-enable from the dashboard once the issue is fixed.
        </div>

        {{-- ═══ signature ═══ --}}
        <h2 id="signature">Signature verification (merchant side)</h2>
        <p>The merchant verifies with their <code>whsec_…</code> secret.</p>

        <ol style="color:var(--muted);line-height:1.9;">
          <li>Parse <code>Stardena-Signature: t=&lt;unix&gt;,v1=&lt;hex&gt;</code> into <code>t</code> and <code>v1</code></li>
          <li>Concatenate <code>&lt;t&gt;.&lt;raw-body&gt;</code></li>
          <li>Compute HMAC-SHA256 with their secret</li>
          <li>Compare with <code>v1</code> using a timing-safe comparison</li>
          <li>Reject if <code>|now - t| &gt; 300</code> seconds</li>
        </ol>

        <p>Copy-paste examples in Node, PHP, Python, and Ruby on the <a href="{{ route('docs.webhooks') }}">webhook reference page</a>.</p>

        {{-- ═══ debug ═══ --}}
        <h2 id="debug">Debugging a missing webhook</h2>
        <p>Follow in order.</p>

        <h3>1. Did the payment succeed?</h3>
        <div class="api-code">
<pre>$payment = Payment::where(<span class="s">'reference'</span>, <span class="s">'ORDER-12345'</span>)-&gt;first();
$payment-&gt;status; <span class="c">// should be 'succeeded'</span></pre>
        </div>

        <h3>2. Did the event get created?</h3>
        <div class="api-code">
<pre>$event = Event::where(<span class="s">'resource_public_id'</span>, $payment-&gt;public_id)
    -&gt;where(<span class="s">'type'</span>, <span class="s">'payment.succeeded'</span>)
    -&gt;first();

<span class="c">// null → EventDispatcher call missing from handleSuccess()</span></pre>
        </div>

        <h3>3. Did the delivery get created?</h3>
        <div class="api-code">
<pre>$deliveries = $event-&gt;deliveries;
$deliveries-&gt;count();

<span class="c">// 0 → no endpoint matched. Check the query.</span>
WebhookEndpoint::where(<span class="s">'company_id'</span>, $payment-&gt;company_id)
    -&gt;where(<span class="s">'mode'</span>, $payment-&gt;mode)
    -&gt;where(<span class="s">'status'</span>, <span class="s">'enabled'</span>)
    -&gt;get()
    -&gt;filter(fn ($e) =&gt; $e-&gt;isSubscribedTo(<span class="s">'payment.succeeded'</span>));</pre>
        </div>

        <h3>4. Did the worker run?</h3>
        <table class="api-table">
          <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
          <tbody>
            <tr><td><code>pending</code></td><td>Queue worker isn't running. Start <code>php artisan queue:work</code>.</td></tr>
            <tr><td><code>failed</code></td><td>Check <code>error_message</code> and <code>response_code</code>.</td></tr>
            <tr><td><code>succeeded</code></td><td>Delivered. Check the merchant's endpoint logs.</td></tr>
          </tbody>
        </table>

        <h3>5. Did the HTTP call fail?</h3>
        <div class="api-code">
<pre>$delivery-&gt;response_code;       <span class="c">// 4xx / 5xx from merchant</span>
$delivery-&gt;error_message;       <span class="c">// DNS failure, timeout, SSL error</span>
$delivery-&gt;response_body;       <span class="c">// merchant's error response</span></pre>
        </div>

        <p>Common causes: endpoint is down, firewall blocks our outbound IPs, invalid SSL cert on their endpoint, timeout too low.</p>

        <h3>6. Was the signature rejected?</h3>
        <p>If the merchant says "signature invalid":</p>
        <ul style="color:var(--muted);line-height:1.9;">
          <li>They parsed and re-stringified the JSON before verifying → signature breaks</li>
          <li>They used their old secret after a rotation</li>
          <li>They compared with <code>==</code> instead of <code>timingSafeEqual</code></li>
        </ul>

        {{-- ═══ files ═══ --}}
        <h2 id="files">Files &amp; responsibilities</h2>
        <table class="api-table">
          <thead><tr><th>File</th><th>Responsibility</th></tr></thead>
          <tbody>
            <tr><td><code>app/Services/Payment/PaymentProcessor.php</code></td><td>Orchestrates payment, calls EventDispatcher</td></tr>
            <tr><td><code>app/Services/Payment/RefundService.php</code></td><td>Fires refund events</td></tr>
            <tr><td><code>app/Services/Payment/PayoutService.php</code></td><td>Fires payout events</td></tr>
            <tr><td><code>app/Services/Webhook/EventDispatcher.php</code></td><td>Creates events, fans out deliveries</td></tr>
            <tr><td><code>app/Jobs/DeliverWebhook.php</code></td><td>Signs and POSTs, handles retries</td></tr>
            <tr><td><code>app/Models/Webhook/WebhookEndpoint.php</code></td><td>Holds secret, <code>signPayload()</code></td></tr>
            <tr><td><code>app/Models/Webhook/Event.php</code></td><td>Immutable record of what happened</td></tr>
            <tr><td><code>app/Models/Webhook/WebhookDelivery.php</code></td><td>One row per delivery attempt</td></tr>
          </tbody>
        </table>

        {{-- ═══ rules ═══ --}}
        <h2 id="rules">Non-negotiables</h2>
        <ol style="color:var(--muted);line-height:1.9;">
          <li><strong>Every webhook is signed.</strong> No exceptions. The merchant must verify.</li>
          <li><strong>The plaintext secret never leaves the server.</strong> Encrypted at rest, masked in the UI.</li>
          <li><strong>Events are immutable.</strong> Never update or delete an <code>events</code> row. Corrections are new events.</li>
          <li><strong>Deliveries are append-only.</strong> Track every attempt. Never overwrite the history.</li>
          <li><strong>Retries use exponential backoff.</strong> No hammering the merchant's endpoint.</li>
          <li><strong>Auto-disable on repeated failure.</strong> Protect the merchant's server from our retry storm.</li>
          <li><strong>Raw body, not parsed.</strong> The signature is over the raw bytes. Any transformation invalidates it.</li>
        </ol>

        <div class="api-callout" style="margin-top:56px;">
          <strong>Related docs:</strong><br>
          <a href="{{ route('docs.api') }}">API reference →</a> ·
          <a href="{{ route('docs.webhooks') }}">Webhook reference →</a>
        </div>

      </main>
    </div>
  </div>
</section>

@endsection

@push('styles')
@include('docs._api_styles')
@endpush