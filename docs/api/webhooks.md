# Webhooks

Stardena sends HTTP POST requests to your server when things happen — a payment
succeeds, a refund completes, a dispute is opened, a payout is paid.

---

## Setting up an endpoint

Dashboard → **Developers → Webhooks → Add Endpoint**.

Fields:

| Field | Notes |
|---|---|
| URL | HTTPS in live mode. HTTP accepted in test. |
| Mode | `test` or `live`. Endpoints are mode-scoped. |
| Events | `["*"]` for everything, or a list (see below). |
| Description | Free-form label |
| Timeout | 10 seconds default, up to 60. |
| Max attempts | 8 default, up to 20. |

Once saved, you get a **signing secret** (`whsec_...`). Copy it into your server config.

---

## Event types

| Event | Fires when |
|---|---|
| `payment.created` | A payment intent is created |
| `payment.processing` | Sent to provider |
| `payment.requires_action` | Customer action needed |
| `payment.succeeded` | Funds captured |
| `payment.failed` | Provider rejected |
| `payment.canceled` | Cancelled before completion |
| `payment.refunded` | Fully refunded |
| `payment.partially_refunded` | Partially refunded |
| `refund.created` | A refund is initiated |
| `refund.succeeded` | Refund completed |
| `refund.failed` | Refund failed |
| `payout.created` | Payout requested |
| `payout.paid` | Payout settled to your bank |
| `payout.failed` | Payout failed |
| `dispute.created` | A chargeback was opened |
| `dispute.updated` | Dispute status changed |
| `dispute.closed` | Dispute resolved |
| `customer.created` | Customer created |
| `customer.updated` | Customer details changed |
| `invoice.paid` | An invoice was paid |

Subscribe with `["*"]` or an explicit list.

---

## The request

```
POST /your/endpoint HTTP/1.1
Content-Type: application/json
User-Agent: Stardena-Webhooks/1.0
Stardena-Event-Id: evt_a1b2c3d4
Stardena-Event-Type: payment.succeeded
Stardena-Signature: t=1737581000,v1=8b3f4c...
Stardena-Delivery-Id: 3c5d7e9f-...
Stardena-Attempt: 1

{
  "id": "evt_a1b2c3d4",
  "type": "payment.succeeded",
  "api_version": "2025-01-01",
  "created": "2026-09-23T00:43:51+00:00",
  "data": {
    "object": {
      "id": "pay_a1b2c3d4e5f6g7h8",
      "object": "payment",
      "status": "succeeded",
      "amount": 50000,
      "amount_captured": 50000,
      "amount_refunded": 0,
      "currency": "UGX",
      "payment_method_type": "mobile_money",
      "reference": "ORDER-12345",
      "metadata": {}
    },
    "previous_attributes": null
  },
  "resource": {
    "type": "payment",
    "id": "pay_a1b2c3d4e5f6g7h8"
  }
}
```

---

## Verifying the signature

**Every** webhook includes a `Stardena-Signature` header. **Verify it before trusting the payload.**

Format: `t=<unix-timestamp>,v1=<hmac-sha256-hex>`

The signed payload is `<timestamp>.<raw-body>`.

### Node.js

```js
const crypto = require('crypto');

function verifyWebhook(rawBody, signatureHeader, secret) {
  const parts = Object.fromEntries(
    signatureHeader.split(',').map(p => p.split('='))
  );
  const timestamp = parts.t;
  const received = parts.v1;

  const signed = `${timestamp}.${rawBody}`;
  const expected = crypto
    .createHmac('sha256', secret)
    .update(signed)
    .digest('hex');

  // Constant-time comparison
  const a = Buffer.from(expected, 'hex');
  const b = Buffer.from(received, 'hex');
  if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) {
    throw new Error('Invalid signature');
  }

  // Reject old events (replay protection)
  const age = Math.abs(Date.now() / 1000 - parseInt(timestamp));
  if (age > 300) throw new Error('Timestamp too old');

  return true;
}
```

### PHP

```php
function verifyWebhook(string $rawBody, string $signatureHeader, string $secret): bool
{
    $parts = [];
    foreach (explode(',', $signatureHeader) as $pair) {
        [$k, $v] = explode('=', $pair, 2);
        $parts[$k] = $v;
    }

    $expected = hash_hmac('sha256', $parts['t'] . '.' . $rawBody, $secret);

    if (!hash_equals($expected, $parts['v1'])) {
        return false;
    }

    if (abs(time() - (int) $parts['t']) > 300) {
        return false;
    }

    return true;
}
```

### Python

```python
import hmac, hashlib, time

def verify_webhook(raw_body: bytes, signature_header: str, secret: str) -> bool:
    parts = dict(p.split('=', 1) for p in signature_header.split(','))
    signed = f"{parts['t']}.{raw_body.decode()}"
    expected = hmac.new(secret.encode(), signed.encode(), hashlib.sha256).hexdigest()

    if not hmac.compare_digest(expected, parts['v1']):
        return False

    if abs(time.time() - int(parts['t'])) > 300:
        return False

    return True
```

### Ruby

```ruby
require 'openssl'

def verify_webhook(raw_body, signature_header, secret)
  parts = signature_header.split(',').map { |p| p.split('=', 2) }.to_h
  signed = "#{parts['t']}.#{raw_body}"
  expected = OpenSSL::HMAC.hexdigest('SHA256', secret, signed)

  return false unless ActiveSupport::SecurityUtils.secure_compare(expected, parts['v1'])
  return false if (Time.now.to_i - parts['t'].to_i).abs > 300

  true
end
```

---

## Responding

Return `2xx` quickly. If you take more than 10 seconds, we consider it failed and retry.

**Do the real work asynchronously.** Acknowledge, then queue.

```
HTTP/1.1 200 OK
```

Any non-2xx, or timeout, triggers a retry.

---

## Retries

Failed deliveries are retried with exponential backoff:

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

After 8 attempts, the delivery is marked `abandoned`. You can retry it manually from the dashboard.

Endpoints that fail **20 consecutive** deliveries are **auto-disabled**. Re-enable them from the dashboard once you've fixed the issue.

---

## Duplicate events

We may deliver the same event more than once (retries, network blips). Deduplicate using `Stardena-Event-Id`.

```js
const seen = new Set();

function handleEvent(event) {
  if (seen.has(event.id)) return; // already processed
  seen.add(event.id);
  // ... process
}
```

In production, store processed event ids in Redis or your database.

---

## Testing

### Trigger a test webhook

Dashboard → **Developers → Webhooks → [endpoint] → Send test event**.

We send a synthetic `payment.succeeded` to your endpoint with a valid signature.

### Use a request bin

Point a test-mode endpoint at `https://webhook.site/your-uuid` and watch live deliveries.

### Replay a failed delivery

Dashboard → **Developers → Webhooks → Deliveries**. Click any delivery, then **Retry**.

---

## API version

Every event carries `api_version`. This is the version of the API contract when the event was generated. If we ever change a payload shape, we'll bump the version and respect your pinned version.

You can pin your endpoint to a specific version in the dashboard.

---

## Common pitfalls

1. **Not verifying the signature.** Anyone can POST to your endpoint. Without verification, an attacker can spoof a `payment.succeeded`.
2. **Doing work in the request handler.** If your handler crashes, we retry. If it hangs, we timeout. Acknowledge in <1 second, then queue.
3. **Not deduplicating.** Retries will re-deliver.
4. **Ignoring `payment_status` on the payment object.** Use the webhook `type` as the source of truth, not the payload's status field (they always match, but the type is contractually stable).
5. **Hardcoding amounts.** Currency exponent varies: UGX has 0 decimals, USD has 2. Always use the minor-unit value.