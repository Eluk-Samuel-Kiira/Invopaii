# Stardena Payments API v1

Programmatic access to Stardena's payment gateway. Charge customers, issue refunds,
manage customers, and receive webhooks when events happen.

Base URL: `https://your-domain.com/api/v1`

---

## Quickstart

### 1. Get an API key

Log in to the dashboard → **Developers → API Keys → Generate Key**.

Two key types you'll use:

| Type | Prefix | Use |
|---|---|---|
| **Secret** | `sk_live_...` / `sk_test_...` | Server-to-server. Never expose to browsers. |
| **Restricted** | `rk_live_...` / `rk_test_...` | Server-to-server, but limited to specific scopes. |

Store the key in an environment variable. It's shown **exactly once**.

### 2. Make your first charge

```bash
curl -X POST https://your-domain.com/api/v1/payments \
  -H "Authorization: Bearer sk_test_xxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: order-12345-attempt-1" \
  -d '{
    "amount": 50000,
    "currency": "UGX",
    "payment_method_type": "mobile_money",
    "customer_email": "customer@example.com",
    "customer_country": "UG",
    "reference": "ORDER-12345",
    "description": "Consulting — March 2026"
  }'
```

Response:

```json
{
  "id": "pay_a1b2c3d4e5f6g7h8",
  "object": "payment",
  "status": "succeeded",
  "amount": 50000,
  "amount_captured": 50000,
  "amount_refunded": 0,
  "currency": "UGX",
  "payment_method_type": "mobile_money",
  "customer_email": "customer@example.com",
  "reference": "ORDER-12345",
  "description": "Consulting — March 2026",
  "metadata": {},
  "created": "2026-09-23T00:43:50+00:00"
}
```

### 3. Handle the result

- `status: succeeded` — money is captured.
- `status: requires_action` — customer needs to authorize (e.g. enter their MoMo PIN). Check `next_action` for details.
- `status: processing` — provider is confirming. You'll get a webhook.
- `status: failed` — check `failure_code` and `failure_message`.

---

## Authentication

Every request must include a Bearer token:

```
Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx
```

Test keys start with `sk_test_`, live keys with `sk_live_`. Test keys can only create test-mode resources. The two are completely isolated — a test key can't see live payments and vice versa.

### Scopes

Restricted keys (`rk_...`) carry a list of scopes. Secret keys (`sk_...`) bypass scope checks.

| Scope | Grants |
|---|---|
| `payments:read` | List and view payments |
| `payments:write` | Create, capture, cancel payments |
| `refunds:read` | List and view refunds |
| `refunds:write` | Issue refunds |
| `customers:read` | List and view customers |
| `customers:write` | Create and update customers |
| `payouts:read` | List and view payouts |
| `balance:read` | View balance |

Example error when a scope is missing:

```json
{
  "error": {
    "type": "permission_error",
    "message": "Missing required scope: payments:write",
    "code": "insufficient_scope"
  }
}
```

### Rate limits

| Key type | Default limit |
|---|---|
| Secret | 600 requests / minute |
| Restricted | 100 requests / minute |

Every response carries:

```
X-RateLimit-Limit: 600
X-RateLimit-Remaining: 587
```

When exceeded:

```
HTTP/1.1 429 Too Many Requests
Retry-After: 42
```

```json
{
  "error": {
    "type": "rate_limit_error",
    "message": "Too many requests.",
    "code": "rate_limit_exceeded"
  }
}
```

### IP allowlisting

Optional. Set `allowed_ips` when creating the key (`["203.0.113.0/24", "198.51.100.42"]`).
Requests from other IPs get `401`.

### Idempotency

For POST/PUT/PATCH/DELETE, pass an `Idempotency-Key` header:

```
Idempotency-Key: order-12345-attempt-1
```

- **Same key + same body** → returns the cached response with `Idempotency-Replayed: true`. Safe to retry.
- **Same key + different body** → `422 idempotency_body_mismatch`.
- **Concurrent request with same key** → `409 idempotency_in_progress`.

Keys expire after 24 hours. Use a fresh UUID or a natural key per request.

```json
{
  "error": {
    "type": "idempotency_error",
    "message": "This Idempotency-Key was used with a different request body.",
    "code": "idempotency_body_mismatch"
  }
}
```

---

## Payments

### The payment object

```json
{
  "id": "pay_a1b2c3d4e5f6g7h8",
  "object": "payment",
  "status": "succeeded",
  "amount": 50000,
  "amount_captured": 50000,
  "amount_refunded": 0,
  "currency": "UGX",
  "payment_method_type": "mobile_money",
  "customer_id": "cus_xyz",
  "customer_email": "customer@example.com",
  "reference": "ORDER-12345",
  "description": "Consulting — March 2026",
  "metadata": {},
  "created": "2026-09-23T00:43:50+00:00"
}
```

Statuses:

| Status | Meaning |
|---|---|
| `requires_payment_method` | Not yet initialized |
| `requires_confirmation` | Initialized, not yet sent to provider |
| `requires_action` | Customer action needed (PIN, 3DS) |
| `processing` | Sent to provider, awaiting result |
| `requires_capture` | Authorized, awaiting capture |
| `succeeded` | Funds captured |
| `partially_refunded` | Some amount refunded |
| `refunded` | Fully refunded |
| `failed` | Provider rejected |
| `cancelled` | Cancelled before completion |
| `expired` | Expired before completion |

### Create a payment

```
POST /api/v1/payments
```

Request body:

| Field | Type | Required | Notes |
|---|---|---|---|
| `amount` | integer | yes | Minor units. 50000 = UGX 50,000. For USD, 5000 = $50.00. |
| `currency` | string | yes | ISO 4217. `UGX`, `KES`, `NGN`, `USD`... |
| `payment_method_type` | string | yes | `card`, `mobile_money`, `bank_transfer`, `ussd`, `wallet` |
| `customer_id` | string | no | Public id of an existing customer |
| `customer_email` | string | no | Used for the receipt |
| `customer_phone` | string | no | MSISDN for mobile money, incl. country code |
| `customer_country` | string | no | ISO2 |
| `reference` | string | no | Your internal id (order, invoice) |
| `description` | string | no | Shows on the customer's receipt |
| `return_url` | string | no | Where to redirect after a redirect-based flow |
| `metadata` | object | no | Any JSON. Echoed back in webhooks. |

Headers:

```
Authorization: Bearer sk_test_...
Idempotency-Key: <unique-per-request>
Content-Type: application/json
```

Response `200` on success, `202` if it needs action or is processing, `402` if failed.

### Retrieve a payment

```
GET /api/v1/payments/{id}
```

`{id}` accepts either the `public_id` (`pay_...`) or the integer `id`.

Response includes extra fields:

```json
{
  "provider": "stub",
  "provider_reference": "stub_6ab320c726ab8",
  "fee_amount": 1750,
  "net_amount": 47935,
  "succeeded_at": "2026-09-23T00:43:51+00:00",
  "failure_code": null,
  "failure_message": null,
  "next_action": null,
  "next_action_type": null
}
```

### List payments

```
GET /api/v1/payments?limit=25
```

Returns the most recent payments for your company, newest first.

### List attempts

```
GET /api/v1/payments/{id}/attempts
```

Every call made to a provider. Useful when a payment fails and you need to see why.

### Cancel a payment

```
POST /api/v1/payments/{id}/cancel
```

Only works for `requires_*` and `processing` states.

### Capture a payment

```
POST /api/v1/payments/{id}/capture
```

Only for `requires_capture` (manual capture flows).

---

## Refunds

### Create a refund

```
POST /api/v1/refunds
```

| Field | Type | Required | Notes |
|---|---|---|---|
| `payment_id` | string | yes | `pay_...` of the original payment |
| `amount` | integer | no | Minor units. Omit for full refund. |
| `reason` | string | no | `requested_by_customer`, `duplicate`, `fraudulent` |
| `description` | string | no | Internal note |

Response `201`:

```json
{
  "id": "ref_xyz",
  "object": "refund",
  "status": "succeeded",
  "amount": 25000,
  "currency": "UGX",
  "payment_id": "pay_a1b2c3d4e5f6g7h8",
  "reason": "requested_by_customer",
  "description": null,
  "is_partial": true,
  "created": "2026-09-23T00:44:00+00:00",
  "processed_at": "2026-09-23T00:44:01+00:00"
}
```

Errors:

| Code | Meaning |
|---|---|
| `payment_not_refundable` | Wrong payment status |
| `amount_exceeds_refundable` | You asked for more than remains |

### Retrieve a refund

```
GET /api/v1/refunds/{id}
```

### List refunds

```
GET /api/v1/refunds?limit=25
```

---

## Customers

### Create

```
POST /api/v1/customers
```

```json
{
  "name": "Amina Nakato",
  "email": "amina@example.com",
  "phone": "+256772100001",
  "country_code": "UG",
  "reference": "CUST-001"
}
```

Email uniqueness is enforced per company + mode.

### Update

```
PUT /api/v1/customers/{id}
```

### Retrieve / List

```
GET /api/v1/customers/{id}
GET /api/v1/customers?limit=25
```

---

## Balance

### Check balance

```
GET /api/v1/balance
```

```json
{
  "object": "balance",
  "data": [
    {
      "currency": "UGX",
      "available": 47000,
      "pending": 0,
      "reserved": 0,
      "in_transit": 0,
      "total": 47000
    }
  ]
}
```

- `available` — withdrawable now
- `pending` — captured, not yet matured (payout delay)
- `reserved` — held against disputes or rolling reserve
- `in_transit` — sent to your bank, not yet confirmed

---

## Payouts

### List

```
GET /api/v1/payouts?limit=25
```

### Retrieve

```
GET /api/v1/payouts/{id}
```

Payouts are created from the dashboard or via scheduled payout runs. The API is read-only for now.

---

## Errors

Every error follows this envelope:

```json
{
  "error": {
    "type": "authentication_error",
    "message": "Invalid API key.",
    "code": "invalid_api_key"
  },
  "request_id": "a1b2c3d4-..."
}
```

| Type | HTTP | Meaning |
|---|---|---|
| `authentication_error` | 401 | Bad or missing key |
| `permission_error` | 403 | Key valid, insufficient scope |
| `validation_error` | 422 | Body failed validation |
| `idempotency_error` | 422 / 409 | Idempotency key conflict |
| `not_found_error` | 404 | Resource doesn't exist |
| `rate_limit_error` | 429 | Too many requests |
| `api_error` | 500 | Server error — contact support |

Every response includes `X-Request-Id` — quote it when contacting support.

---

## Webhooks

See `docs/api/webhooks.md` for the full reference.

---

## SDKs

None yet. The API is plain HTTP + JSON. Use `curl`, `axios`, `HttpClient`, `Guzzle`, or any HTTP client.

---

## Support

- Dashboard: `https://your-domain.com/admin`
- Email: support@your-domain.com
- Status: `https://status.your-domain.com`