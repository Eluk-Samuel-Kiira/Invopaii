@extends('layouts.home.app')

@section('title', 'Stardena Pay — Webhooks')
@section('description', 'Signed HTTP events for every charge, refund and payout on Stardena Pay.')

@section('content')

<section style="padding:0;">
  <div class="wrap" style="max-width:1240px;">
    <div class="api-layout">

      {{-- ═══ sidebar ═══ --}}
      <aside class="api-side" aria-label="Webhook reference navigation">
        <div style="font-family:var(--display);font-weight:700;color:#fff;font-size:18px;letter-spacing:-.01em;">Stardena</div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:22px;">Webhooks v1</div>

        <p class="api-nav-group">Reference</p>
        <a href="#envelope">Event envelope</a>
        <a href="#event-types">Event types</a>
        <a href="#request">The request</a>
        <a href="#signature">Signature</a>
        <a href="#verify">Verify (Node)</a>
        <a href="#verify-php">Verify (PHP)</a>
        <a href="#verify-python">Verify (Python)</a>
        <a href="#verify-ruby">Verify (Ruby)</a>

        <p class="api-nav-group">Handling</p>
        <a href="#rules">Three rules</a>
        <a href="#retries">Retries</a>
        <a href="#dedupe">Deduplication</a>
        <a href="#testing">Testing</a>

        <p class="api-nav-group">Related</p>
        <a href="{{ route('docs.api') }}">← API reference</a>
      </aside>

      {{-- ═══ content ═══ --}}
      <main class="api-main">

        <p class="eyebrow">Webhooks</p>
        <h1 style="font-size:clamp(28px,3.4vw,38px);">Webhook reference</h1>
        <p class="lede" style="max-width:60ch;">Signed HTTP events for payments, refunds, disputes and payouts. Every delivery carries a stable envelope and an HMAC-SHA256 signature.</p>

        {{-- ═══ envelope ═══ --}}
        <h2 id="envelope">Event envelope</h2>
        <p>Every webhook has the same shape. <code>type</code> tells you what happened; <code>data.object</code> carries the resource.</p>

        <div class="api-code">
<pre>{
  <span class="s">"id"</span>: <span class="s">"evt_a1b2c3d4"</span>,
  <span class="s">"type"</span>: <span class="s">"payment.succeeded"</span>,
  <span class="s">"api_version"</span>: <span class="s">"2025-01-01"</span>,
  <span class="s">"created"</span>: <span class="s">"2026-09-23T00:43:51+00:00"</span>,
  <span class="s">"data"</span>: {
    <span class="s">"object"</span>: {
      <span class="s">"id"</span>: <span class="s">"pay_a1b2c3d4"</span>,
      <span class="s">"amount"</span>: <span class="n">50000</span>,
      <span class="s">"currency"</span>: <span class="s">"UGX"</span>,
      <span class="s">"status"</span>: <span class="s">"succeeded"</span>
    },
    <span class="s">"previous_attributes"</span>: <span class="k">null</span>
  },
  <span class="s">"resource"</span>: {
    <span class="s">"type"</span>: <span class="s">"payment"</span>,
    <span class="s">"id"</span>: <span class="s">"pay_a1b2c3d4"</span>
  }
}</pre>
        </div>

        {{-- ═══ event types ═══ --}}
        <h2 id="event-types">Event types</h2>
        <p>Subscribe with <code>["*"]</code> for everything, or a list of specific events.</p>

        <table class="api-table">
          <thead><tr><th>Event</th><th>Fires when</th></tr></thead>
          <tbody>
            <tr><td><code>payment.created</code></td><td>A payment intent is created</td></tr>
            <tr><td><code>payment.processing</code></td><td>Sent to provider</td></tr>
            <tr><td><code>payment.requires_action</code></td><td>Customer action needed (PIN, 3DS)</td></tr>
            <tr><td><code>payment.succeeded</code></td><td>Funds captured</td></tr>
            <tr><td><code>payment.failed</code></td><td>Provider rejected</td></tr>
            <tr><td><code>payment.canceled</code></td><td>Cancelled before completion</td></tr>
            <tr><td><code>payment.refunded</code></td><td>Fully refunded</td></tr>
            <tr><td><code>payment.partially_refunded</code></td><td>Partially refunded</td></tr>
            <tr><td><code>refund.created</code></td><td>A refund is initiated</td></tr>
            <tr><td><code>refund.succeeded</code></td><td>Refund completed</td></tr>
            <tr><td><code>refund.failed</code></td><td>Refund failed</td></tr>
            <tr><td><code>payout.created</code></td><td>Payout requested</td></tr>
            <tr><td><code>payout.paid</code></td><td>Payout settled to bank</td></tr>
            <tr><td><code>payout.failed</code></td><td>Payout failed</td></tr>
            <tr><td><code>dispute.created</code></td><td>A chargeback was opened</td></tr>
            <tr><td><code>dispute.updated</code></td><td>Dispute status changed</td></tr>
            <tr><td><code>dispute.closed</code></td><td>Dispute resolved</td></tr>
            <tr><td><code>customer.created</code></td><td>Customer created</td></tr>
            <tr><td><code>customer.updated</code></td><td>Customer details changed</td></tr>
            <tr><td><code>invoice.paid</code></td><td>An invoice was paid</td></tr>
          </tbody>
        </table>

        {{-- ═══ request ═══ --}}
        <h2 id="request">The request</h2>

        <div class="api-code">
<pre>POST /your/endpoint HTTP/1.1
Content-Type: application/json
User-Agent: Stardena-Webhooks/1.0
Stardena-Event-Id: evt_a1b2c3d4
Stardena-Event-Type: payment.succeeded
Stardena-Signature: t=1737581000,v1=8b3f4c…
Stardena-Delivery-Id: 3c5d7e9f-…
Stardena-Attempt: 1</pre>
        </div>

        <table class="api-table">
          <thead><tr><th>Header</th><th>Purpose</th></tr></thead>
          <tbody>
            <tr><td><code>Stardena-Event-Id</code></td><td>Use for deduplication</td></tr>
            <tr><td><code>Stardena-Event-Type</code></td><td>Same as <code>type</code> in the body</td></tr>
            <tr><td><code>Stardena-Signature</code></td><td>HMAC verification</td></tr>
            <tr><td><code>Stardena-Delivery-Id</code></td><td>Unique per attempt (changes on retry)</td></tr>
            <tr><td><code>Stardena-Attempt</code></td><td>1-indexed attempt counter</td></tr>
          </tbody>
        </table>

        {{-- ═══ signature ═══ --}}
        <h2 id="signature">Signature</h2>
        <p>Every webhook is signed. Verify before you act on the payload.</p>

        <table class="api-table">
          <thead><tr><th>Item</th><th>Value</th></tr></thead>
          <tbody>
            <tr><td>Algorithm</td><td>HMAC-SHA256</td></tr>
            <tr><td>Header</td><td><code>Stardena-Signature</code></td></tr>
            <tr><td>Format</td><td><code>t=&lt;unix&gt;,v1=&lt;hex&gt;</code></td></tr>
            <tr><td>Signed payload</td><td><code>&lt;timestamp&gt;.&lt;raw-body&gt;</code></td></tr>
            <tr><td>Replay window</td><td>Reject if <code>|now - t| &gt; 300</code> seconds</td></tr>
          </tbody>
        </table>

        <div class="api-callout warn">
          Use <code>hash_equals</code> / <code>timingSafeEqual</code> — never <code>==</code>. Timing attacks are real.
        </div>

        {{-- ═══ verify node ═══ --}}
        <h2 id="verify">Verify — Node.js</h2>

        <div class="api-code">
<pre><span class="k">const</span> crypto = <span class="n">require</span>(<span class="s">"crypto"</span>);

<span class="k">function</span> <span class="n">verifyWebhook</span>(rawBody, header, secret) {
  <span class="k">const</span> parts = Object.<span class="n">fromEntries</span>(
    header.<span class="n">split</span>(<span class="s">","</span>).<span class="n">map</span>(p =&gt; p.<span class="n">split</span>(<span class="s">"="</span>))
  );

  <span class="k">const</span> signed = <span class="s">`${parts.t}.${rawBody}`</span>;
  <span class="k">const</span> expected = crypto
    .<span class="n">createHmac</span>(<span class="s">"sha256"</span>, secret)
    .<span class="n">update</span>(signed)
    .<span class="n">digest</span>(<span class="s">"hex"</span>);

  <span class="k">const</span> a = Buffer.<span class="n">from</span>(expected, <span class="s">"hex"</span>);
  <span class="k">const</span> b = Buffer.<span class="n">from</span>(parts.v1, <span class="s">"hex"</span>);
  <span class="k">if</span> (a.length !== b.length || !crypto.<span class="n">timingSafeEqual</span>(a, b)) {
    <span class="k">throw new</span> <span class="n">Error</span>(<span class="s">"invalid signature"</span>);
  }

  <span class="k">if</span> (Math.<span class="n">abs</span>(Date.<span class="n">now</span>() / 1000 - <span class="n">Number</span>(parts.t)) &gt; <span class="n">300</span>) {
    <span class="k">throw new</span> <span class="n">Error</span>(<span class="s">"timestamp too old"</span>);
  }
}</pre>
        </div>

        <div class="api-callout">
          Capture the <strong>raw body</strong> before your JSON parser touches it. Most frameworks mutate the body — signatures fail if you hash the parsed form.
        </div>

        {{-- ═══ verify php ═══ --}}
        <h2 id="verify-php">Verify — PHP</h2>

        <div class="api-code">
<pre><span class="k">function</span> <span class="n">verifyWebhook</span>(string $rawBody, string $header, string $secret): bool
{
    $parts = [];
    <span class="k">foreach</span> (explode(<span class="s">','</span>, $header) <span class="k">as</span> $pair) {
        [$k, $v] = explode(<span class="s">'='</span>, $pair, <span class="n">2</span>);
        $parts[$k] = $v;
    }

    $expected = hash_hmac(<span class="s">'sha256'</span>, $parts[<span class="s">'t'</span>] . <span class="s">'.'</span> . $rawBody, $secret);

    <span class="k">if</span> (!hash_equals($expected, $parts[<span class="s">'v1'</span>])) {
        <span class="k">return false</span>;
    }

    <span class="k">if</span> (abs(time() - (int) $parts[<span class="s">'t'</span>]) &gt; <span class="n">300</span>) {
        <span class="k">return false</span>;
    }

    <span class="k">return true</span>;
}</pre>
        </div>

        {{-- ═══ verify python ═══ --}}
        <h2 id="verify-python">Verify — Python</h2>

        <div class="api-code">
<pre><span class="k">import</span> hmac, hashlib, time

<span class="k">def</span> <span class="n">verify_webhook</span>(raw_body: bytes, header: str, secret: str) -&gt; bool:
    parts = dict(p.split(<span class="s">"="</span>, <span class="n">1</span>) <span class="k">for</span> p <span class="k">in</span> header.split(<span class="s">","</span>))
    signed = f<span class="s">"{parts['t']}.{raw_body.decode()}"</span>
    expected = hmac.new(secret.encode(), signed.encode(), hashlib.sha256).hexdigest()

    <span class="k">if not</span> hmac.compare_digest(expected, parts[<span class="s">'v1'</span>]):
        <span class="k">return False</span>

    <span class="k">if</span> abs(time.time() - int(parts[<span class="s">'t'</span>])) &gt; <span class="n">300</span>:
        <span class="k">return False</span>

    <span class="k">return True</span></pre>
        </div>

        {{-- ═══ verify ruby ═══ --}}
        <h2 id="verify-ruby">Verify — Ruby</h2>

        <div class="api-code">
<pre><span class="k">require</span> <span class="s">'openssl'</span>

<span class="k">def</span> <span class="n">verify_webhook</span>(raw_body, header, secret)
  parts = header.split(<span class="s">','</span>).map { |p| p.split(<span class="s">'='</span>, <span class="n">2</span>) }.to_h
  signed = <span class="s">"#{parts['t']}.#{raw_body}"</span>
  expected = OpenSSL::HMAC.hexdigest(<span class="s">'SHA256'</span>, secret, signed)

  <span class="k">return false unless</span> Rack::Utils.secure_compare(expected, parts[<span class="s">'v1'</span>])
  <span class="k">return false if</span> (Time.now.to_i - parts[<span class="s">'t'</span>].to_i).abs &gt; <span class="n">300</span>

  <span class="k">true</span>
<span class="k">end</span></pre>
        </div>

        {{-- ═══ rules ═══ --}}
        <h2 id="rules">Three rules</h2>

        <table class="api-table">
          <thead><tr><th>Rule</th><th>Why</th></tr></thead>
          <tbody>
            <tr>
              <td><strong>Acknowledge in &lt;1s</strong></td>
              <td>Return <code>2xx</code> immediately. Queue the work. If we timeout at 10s, we retry.</td>
            </tr>
            <tr>
              <td><strong>Deduplicate by event id</strong></td>
              <td>We may deliver the same event twice. Store <code>evt_…</code> as a processed key.</td>
            </tr>
            <tr>
              <td><strong>Verify the signature</strong></td>
              <td>Anyone can POST to your endpoint. Reject invalid signatures with <code>401</code>.</td>
            </tr>
          </tbody>
        </table>

        {{-- ═══ retries ═══ --}}
        <h2 id="retries">Retries</h2>
        <p>Any non-2xx, timeout, or connection failure triggers a retry. Backoff is exponential:</p>

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

        <p>After 8 attempts: <code>abandoned</code>. Endpoints failing <strong>20 consecutive</strong> deliveries are <strong>auto-disabled</strong> — re-enable from the dashboard.</p>

        {{-- ═══ dedupe ═══ --}}
        <h2 id="dedupe">Deduplication</h2>
        <p>Store processed event ids for at least 7 days. Redis works well; a unique constraint on <code>events.event_id</code> in your database works too.</p>

        <div class="api-code">
<pre><span class="c">// Redis example</span>
<span class="k">const</span> key = <span class="s">`stardena:event:${event.id}`</span>;
<span class="k">const</span> seen = <span class="k">await</span> redis.<span class="n">set</span>(key, <span class="s">"1"</span>, <span class="s">"NX"</span>, <span class="s">"EX"</span>, <span class="n">604800</span>);
<span class="k">if</span> (!seen) <span class="k">return</span>; <span class="c">// already processed</span>
<span class="c">// ... handle the event</span></pre>
        </div>

        {{-- ═══ testing ═══ --}}
        <h2 id="testing">Testing</h2>

        <table class="api-table">
          <thead><tr><th>Method</th><th>How</th></tr></thead>
          <tbody>
            <tr><td>Send test event</td><td>Dashboard → Developers → Webhooks → [endpoint] → <strong>Send test event</strong></td></tr>
            <tr><td>Request bin</td><td>Point a test-mode endpoint at <code>https://webhook.site/your-uuid</code></td></tr>
            <tr><td>Replay a failed delivery</td><td>Dashboard → Webhooks → Deliveries → [delivery] → <strong>Retry</strong></td></tr>
          </tbody>
        </table>

        <div class="api-callout">
          <strong>Related:</strong> <a href="{{ route('docs.api') }}">API reference →</a> · <a href="{{ route('docs.api') }}#idempotency">Idempotency →</a>
        </div>

      </main>
    </div>
  </div>
</section>

@endsection

@push('styles')
<style>
/* ─────────────────────────────────────────────
   Webhook docs — reuses .api-layout classes.
   Identical to the API reference page's styles
   so the two pages look and feel the same.
   ───────────────────────────────────────────── */

.api-layout{
  display:grid;
  grid-template-columns:220px minmax(0,1fr);
  gap:56px;
  padding:56px 0 120px;
  align-items:start;
}

.api-side{
  position:sticky;top:20px;
  align-self:start;
  font-size:14px;line-height:1.4;
  max-height:calc(100vh - 40px);
  overflow-y:auto;
  padding-right:8px;
}
.api-side::-webkit-scrollbar{width:4px}
.api-side::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:4px}

.api-nav-group{
  font-size:11.5px;font-weight:600;
  letter-spacing:.08em;text-transform:uppercase;
  color:var(--accent-soft);
  margin:22px 0 6px;
}
.api-nav-group:first-of-type{margin-top:0}

.api-side a{display:block;padding:5px 0;color:var(--muted);text-decoration:none;font-size:14px;transition:color .1s ease}
.api-side a:hover{color:#fff}

.api-main{min-width:0;font-size:15.5px;line-height:1.7;color:#fff}
.api-main p{color:var(--muted);margin:12px 0}

.api-main h2{
  font-family:var(--display);
  font-size:22px;font-weight:700;letter-spacing:-.01em;
  margin:56px 0 14px;padding-top:24px;
  border-top:1px solid var(--line);
  color:#fff;scroll-margin-top:24px;
}
.api-main h2:first-of-type{border-top:0;padding-top:0;margin-top:24px}

.api-main h3{
  font-family:var(--display);
  font-size:16.5px;font-weight:600;
  margin:28px 0 8px;color:#fff;
}

.api-main code{
  font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
  font-size:.9em;
  background:rgba(255,255,255,.08);
  padding:2px 6px;border-radius:5px;
  color:#E7DDFF;
}

.api-main a{color:var(--accent-soft)}
.api-main a:hover{color:#fff}

.api-callout{
  padding:14px 18px;
  border-left:3px solid var(--violet);
  background:rgba(110,63,231,.12);
  border-radius:0 8px 8px 0;
  margin:20px 0;font-size:14.5px;color:var(--muted);
}
.api-callout strong{color:#fff}
.api-callout.warn{border-left-color:#f59e0b;background:rgba(245,158,11,.1)}

.api-code{
  background:#160C2B;
  border:1px solid rgba(255,255,255,.08);
  border-radius:12px;overflow:hidden;margin:16px 0;
}
.api-code pre{
  margin:0;padding:18px 20px;
  font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
  font-size:13px;line-height:1.65;overflow-x:auto;color:#D9CDF5;
}
.api-code .k{color:#FF9E64}
.api-code .s{color:#9BE7A8}
.api-code .c{color:#8574B5;font-style:italic}
.api-code .n{color:#7FD3FF}

.api-table{width:100%;border-collapse:collapse;margin:18px 0;font-size:14px}
.api-table th,
.api-table td{padding:10px 14px;text-align:left;border-bottom:1px solid var(--line);vertical-align:top}
.api-table th{
  font-weight:600;color:var(--accent-soft);
  font-size:12px;letter-spacing:.06em;text-transform:uppercase;
  background:rgba(255,255,255,.02);
}
.api-table td{color:var(--muted)}
.api-table td code{color:#E7DDFF;background:rgba(110,63,231,.18)}
.api-table td strong{color:#fff}

@media (max-width:900px){
  .api-layout{grid-template-columns:1fr;gap:28px;padding:32px 0 80px}
  .api-side{
    position:static;max-height:none;
    border:1px solid var(--line);border-radius:14px;
    padding:20px;background:rgba(255,255,255,.02);
  }
  .api-main h2{font-size:19px;margin:40px 0 12px;padding-top:20px}
  .api-table{font-size:13px}
  .api-table th,.api-table td{padding:8px 10px}
}
</style>
@endpush