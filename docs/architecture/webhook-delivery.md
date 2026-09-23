# Webhook delivery pipeline

How Stardena fires outbound webhooks. Covers payments, refunds, payouts,
disputes, and customer changes.

**Read this before touching:**
- `app/Services/Payment/PaymentProcessor.php`
- `app/Services/Webhook/EventDispatcher.php`
- `app/Jobs/DeliverWebhook.php`
- `app/Models/Webhook/WebhookEndpoint.php`
- `app/Models/Webhook/Event.php`
- `app/Models/Webhook/WebhookDelivery.php`

---

## The full pipeline

```
1. Merchant action (API, dashboard, subscription job, refund)
   ↓
2. PaymentIntentService::create()       → creates `payments` row
   ↓
3. PaymentProcessor::process()          → routes to provider, creates `payment_attempts`
   ↓
4. Provider charges                     → returns ProviderResult
   ↓
5. PaymentProcessor::handleSuccess()    → runs all post-payment side effects
   ↓
6. FeeService::applyProcessingFee()     → writes `applied_fees`, sets payment.fee_amount
   ↓
7. LedgerService::post()                → writes `ledger_transactions` + `ledger_entries`
   ↓
8. creditMerchantBalance()              → updates `balances`, writes `balance_transactions`
   ↓
9. AuditLog::record()                   → writes `audit_logs`
   ↓
10. notifyPaymentSucceeded()            → writes `notifications`
    ↓
11. EventDispatcher::dispatch()         → writes `events`, fans out to `webhook_deliveries`
    ↓
12. DeliverWebhook job (queued)         → signs payload, POSTs to endpoint URL
    ↓
13. Merchant server receives it         → verifies signature, updates their system
```

Every step is a separate concern. Change one without touching the others.

---

## Step 1 — Endpoint registration

A webhook endpoint lives in `webhook_endpoints`:

```php
$endpoint = $company->webhookEndpoints()->create([
    'mode' => 'test',                                  // or 'live'
    'url' => 'https://merchant.example.com/webhooks',
    'description' => 'Production webhook',
    'enabled_events' => ['*'],                         // or explicit list
    'status' => 'enabled',
    'timeout_seconds' => 10,
    'max_attempts' => 8,
]);
```

The model auto-generates a `whsec_...` signing secret on create. It's stored
**encrypted** in the `secret` column. Read back decrypted; masked for display.

- `$endpoint->secret` — the full plaintext secret (used for signing)
- `$endpoint->masked_secret` — what the dashboard shows

**Rule:** the plaintext secret must never leave the server, never appear in an
API response, never be logged.

---

## Step 2 — A payment is created and processed

```php
$payment = PaymentIntentService::createAndProcess($company, [
    'mode' => 'test',
    'source' => 'api',
    'customer_id' => $customer->id,
    'currency' => 'UGX',
    'amount' => 25000,
    'payment_method_type' => 'mobile_money',
    'customer_country' => 'UG',
    'reference' => 'ORDER-12345',
]);
```

`createAndProcess()` does:

1. `create()` — resolves a provider via `ProviderRouter::route([...])`, inserts
   a `payments` row in `requires_confirmation`.
2. `process()` — resolves the provider service class, creates a `payment_attempts`
   row, calls `$service->charge($attempt, $credential)`.
3. The provider returns a `ProviderResult` object.

---

## Step 3 — handleSuccess runs the post-payment chain

Inside one `DB::transaction()`:

```php
$payment = PaymentStateMachine::transition($payment, 'succeeded', [
    'provider_reference' => $result->providerReference,
    'amount_captured' => $payment->amount,
    'captured_at' => now(),
]);

$appliedFee = FeeService::applyProcessingFee($payment);
$feeTotal = $appliedFee?->total_amount ?? 0;

self::postPaymentLedger($payment, $feeTotal);
self::creditMerchantBalance($payment, $feeTotal);

\App\Models\Platform\AuditLog::record([
    'action' => 'payment.succeeded',
    // ...
]);

self::notifyPaymentSucceeded($payment, $feeTotal);

// 👇 This is the webhook trigger
\App\Services\Webhook\EventDispatcher::dispatch(
    company: $payment->company,
    type: 'payment.succeeded',
    data: self::paymentEventPayload($payment, $feeTotal),
    resource: $payment,
    origin: 'system',
    mode: $payment->mode,
);
```

Everything inside the transaction is atomic. If any step fails, the payment
fails and rolls back. Webhook dispatch is one of the last steps.

---

## Step 4 — EventDispatcher::dispatch does three things

### A. Records the event

```php
$event = Event::create([
    'company_id' => $company->id,
    'mode' => $effectiveMode,
    'type' => 'payment.succeeded',
    'resource_type' => 'App\Models\Payment\Payment',
    'resource_id' => $payment->id,
    'resource_public_id' => $payment->public_id,
    'data' => [...],        // full payment snapshot
    'api_version' => '2025-01-01',
    'origin' => 'system',
    'pending_webhooks' => 0,
]);
```

### B. Finds subscribed endpoints

```php
$endpoints = WebhookEndpoint::where('company_id', $company->id)
    ->where('mode', $event->mode)
    ->where('status', 'enabled')
    ->get()
    ->filter(fn ($e) => $e->isSubscribedTo('payment.succeeded'));
```

If none match, `pending_webhooks` stays 0 and nothing queues.

### C. Creates one delivery per endpoint and queues the job

```php
$delivery = WebhookDelivery::create([
    'webhook_endpoint_id' => $endpoint->id,
    'event_id' => $event->id,
    'company_id' => $company->id,
    'mode' => $event->mode,
    'event_type' => 'payment.succeeded',
    'status' => 'pending',
    'attempt' => 0,
    'scheduled_for' => now(),
    'next_retry_at' => now(),
]);

\App\Jobs\DeliverWebhook::dispatch($delivery);
```

If `QUEUE_CONNECTION=sync`, the job runs inline. Otherwise it goes into the
`jobs` table and is picked up by `php artisan queue:work`.

---

## Step 5 — DeliverWebhook signs and POSTs

The job:

1. Reloads the delivery with `endpoint` and `event`
2. Bumps `attempt` from N to N+1
3. Builds the JSON body via `$event->toWebhookPayload()`
4. Signs it:

```php
$timestamp = time();
$signature = $endpoint->signPayload($body, $timestamp);
```

`WebhookEndpoint::signPayload()` on the model:

```php
public function signPayload(string $body, int $timestamp): string
{
    $signed = $timestamp . '.' . $body;
    $hex = hash_hmac('sha256', $signed, $this->secret);
    return "t={$timestamp},v1={$hex}";
}
```

5. Sends the POST with these headers:

```
Content-Type: application/json
User-Agent: Stardena-Webhooks/1.0
Stardena-Event-Id: evt_...
Stardena-Event-Type: payment.succeeded
Stardena-Signature: t=...,v1=...
Stardena-Delivery-Id: <uuid>
Stardena-Attempt: 1
```

6. On 2xx → mark `succeeded`, set `last_success_at`, reset `consecutive_failures`.
7. On failure → mark `failed`, increment `consecutive_failures`, schedule retry.

---

## The event payload

This is what the merchant receives. Same shape for every event.

```json
{
  "id": "evt_ifhg3hfrgseenlz8zpivayjn",
  "type": "payment.succeeded",
  "api_version": "2025-01-01",
  "created": "2026-09-23T04:50:07+00:00",
  "data": {
    "object": { ...payment fields... },
    "previous_attributes": null
  },
  "resource": {
    "type": "App\\Models\\Payment\\Payment",
    "id": "pay_pldnxnq1clre5f3mnun4nrih"
  }
}
```

Field origins:

| Field | Column |
|---|---|
| `id` | `events.public_id` |
| `type` | `events.type` |
| `api_version` | `events.api_version` |
| `created` | `events.created_at` (ISO8601) |
| `data.object` | Controller-specific payload builder |
| `data.previous_attributes` | `events.previous_attributes` |
| `resource.type` | `events.resource_type` (FQCN) |
| `resource.id` | `events.resource_public_id` |

---

## Retry policy

Failed deliveries retry with exponential backoff.

| Attempt | Delay |
|---|---|
| 1 | immediate |
| 2 | 30 seconds |
| 3 | 5 minutes |
| 4 | 30 minutes |
| 5 | 2 hours |
| 6 | 6 hours |
| 7 | 12 hours |
| 8 | 24 hours |

After the 8th attempt: `abandoned`. Operators can retry manually from the
dashboard.

**Auto-disable:** endpoints failing **20 consecutive** deliveries are set to
`auto_disabled`. Re-enable from the dashboard once the issue is fixed.

---

## Signature verification (merchant side)

The merchant verifies with their `whsec_...` secret. Algorithm:

1. Parse `Stardena-Signature: t=<unix>,v1=<hex>` into `t` and `v1`
2. Concatenate `<t>.<raw-body>`
3. Compute `HMAC-SHA256` with their secret
4. Compare with `v1` using a timing-safe comparison
5. Reject if `|now - t| > 300` seconds

See the "Webhook reference" docs page for copy-paste examples in Node, PHP,
Python, and Ruby.

---

## Debugging a missing webhook

Follow this checklist in order:

### 1. Did the payment succeed?

```php
$payment = Payment::where('reference', 'ORDER-12345')->first();
$payment->status; // should be 'succeeded'
```

### 2. Did the event get created?

```php
$event = Event::where('resource_public_id', $payment->public_id)
    ->where('type', 'payment.succeeded')
    ->first();
```

If null → the `EventDispatcher::dispatch()` call is missing from
`PaymentProcessor::handleSuccess()`.

### 3. Did the delivery get created?

```php
$deliveries = $event->deliveries;
$deliveries->count();
```

If 0 → no endpoint matched. Verify:

```php
WebhookEndpoint::where('company_id', $payment->company_id)
    ->where('mode', $payment->mode)
    ->where('status', 'enabled')
    ->get()
    ->filter(fn ($e) => $e->isSubscribedTo('payment.succeeded'));
```

### 4. Did the worker run?

```php
$delivery = $deliveries->first();
$delivery->status;
```

- `pending` → the queue worker isn't running. Start `php artisan queue:work`.
- `failed` → check `$delivery->error_message` and `$delivery->response_code`.
- `succeeded` → the webhook was delivered. Check the merchant's endpoint logs.

### 5. Did the HTTP call fail?

```php
$delivery->response_code;       // 4xx / 5xx from merchant
$delivery->error_message;       // DNS failure, timeout, SSL error
$delivery->response_body;       // merchant's error response
```

Common causes:

- Endpoint is down
- Firewall blocks our outbound IPs
- SSL certificate invalid on the merchant's endpoint
- Timeout too low for their processing

### 6. Was the signature rejected?

If the merchant says "signature invalid":

```php
$endpoint = $delivery->endpoint;
$endpoint->secret;              // must match what they stored
$endpoint->masked_secret;       // what they see in the dashboard
```

Common causes:

- They parsed and re-stringified the JSON before verifying → signature breaks
- They used their old secret after a rotation
- They compared with `==` instead of `timingSafeEqual`

---

## Files & responsibilities

| File | Responsibility |
|---|---|
| `app/Services/Payment/PaymentProcessor.php` | Orchestrates the payment, calls `EventDispatcher` |
| `app/Services/Payment/RefundService.php` | Calls `EventDispatcher` on refund events |
| `app/Services/Payment/PayoutService.php` | Calls `EventDispatcher` on payout events |
| `app/Services/Webhook/EventDispatcher.php` | Creates `events`, fans out deliveries |
| `app/Jobs/DeliverWebhook.php` | Signs and POSTs, handles retries |
| `app/Models/Webhook/WebhookEndpoint.php` | Holds the signing secret, `signPayload()` |
| `app/Models/Webhook/Event.php` | Immutable record of what happened |
| `app/Models/Webhook/WebhookDelivery.php` | One row per delivery attempt |

---

## Non-negotiables

1. **Every webhook is signed.** No exceptions. The merchant must verify.
2. **The plaintext secret never leaves the server.** Encrypted at rest, masked
   in the UI.
3. **Events are immutable.** Never update or delete an `events` row. Corrections
   are new events.
4. **Deliveries are append-only.** Track every attempt. Never overwrite the
   history.
5. **Retries use exponential backoff.** No hammering the merchant's endpoint.
6. **Auto-disable on repeated failure.** Protect the merchant's server from
   our retry storm.
7. **Raw body, not parsed.** The signature is over the raw bytes. Any
   transformation invalidates it.