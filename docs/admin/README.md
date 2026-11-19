# Stardena Admin Guide

You're the operator of a payment gateway. This document covers the workflows
you'll use daily.

---

## Table of contents

1. [Company lifecycle](#company-lifecycle)
2. [Compliance / KYB](#compliance--kyb)
3. [Payment providers](#payment-providers)
4. [Fee schedules](#fee-schedules)
5. [Payments & disputes](#payments--disputes)
6. [Payouts](#payouts)
7. [Risk](#risk)
8. [Audit logs](#audit-logs)
9. [Reference data](#reference-data)
10. [Users & roles](#users--roles)
11. [Common tasks](#common-tasks)

---

## Company lifecycle

A company moves through:

```
pending → in_review → active → (restricted | suspended | closed)
                    ↘ rejected
```

| Status | Meaning | Charges? | Payouts? |
|---|---|---|---|
| `pending` | Signed up, hasn't submitted KYB | No | No |
| `in_review` | KYB submitted, compliance reviewing | No | No |
| `active` | Fully onboarded | Yes | Yes |
| `restricted` | Charges allowed, payouts held | Yes | No |
| `suspended` | Temporarily shut down | No | No |
| `rejected` | Terminal — application denied | No | No |
| `closed` | Terminal — merchant left | No | No |

**To change a company's status:** Companies → [company] → Change Status.

The status dropdown shows a hint for each option. Destructive statuses
(`suspended`, `rejected`, `closed`) require a reason.

**Guardrails:**

- `active` requires `kyb_status = verified`
- Moving to a destructive status auto-disables `live_mode`, `charges_enabled`, and `payouts_enabled`
- `rejected` and `closed` are terminal — no way back through the UI

---

## Compliance / KYB

A merchant's compliance tab has three sub-tabs:

### Representatives

Directors, UBOs, signatories. Each entry has:

- Personal info (name, DOB, nationality, job title)
- Roles: Director, Owner/UBO, Signatory, Primary Contact
- Ownership % (required if UBO)
- ID document (type, number, expiry)

**ID numbers are encrypted at rest.** The UI shows a masked version (`••••••1234`).
You can never see the full number — that's by design.

### Documents

Uploaded KYB files: certificate of incorporation, tax cert, bank statement,
utility bill, ID front/back, selfie, memorandum & articles.

Each document has a status: `pending`, `approved`, `rejected`, `expired`.

- **Approve** (green check) — documents satisfy the requirement
- **Reject** (red cross) — will prompt for a note
- **Preview** (eye icon) — inline view of the file

### Verification Checks

Automated or manual checks against the company:

| Check type | What it does |
|---|---|
| `kyb_registry` | Cross-check registration number against the local business registry |
| `aml_screening` | Screen representatives against AML databases |
| `sanctions` | Screen against OFAC / UN / EU lists |
| `pep` | Politically Exposed Person check |
| `document_ocr` | Extract text from uploaded docs |
| `liveness` | Selfie verification |
| `bank_account` | Micro-deposit or name-lookup verification |

Every check is logged with request/response payloads. **Never delete these.** They're your AML audit trail.

**When to move a company to `active`:**

1. All required documents are `approved`
2. All required checks are `passed`
3. `kyb_status` is manually set to `verified` (in the company edit modal)
4. Then change status to `active`

The system won't stop you from activating early, but compliance risk sits with you.

---

## Payment providers

Dashboard → **Providers**.

Lists every upstream processor: MTN, Airtel, M-PESA, Flutterwave, DPO, PayPal, Pesapal, plus a `stub` for testing.

### Provider properties

| Field | Meaning |
|---|---|
| Type | `mobile_money`, `acquirer`, `aggregator`, `wallet`, `bank` |
| Supported countries | Which markets this provider serves |
| Supported currencies | What it can settle in |
| Supported methods | Which payment methods it handles |
| Priority | Lower = tried first in routing fallback |
| Health | `healthy`, `degraded`, `down` |
| Test mode | Whether it has a sandbox |

### Credentials

Providers need API keys. Click the key icon on a provider to manage:

- **Scope**: Platform-wide (Stardena-managed) or per-merchant
- **Mode**: `test` or `live`
- **Public key / Secret key / Webhook secret**: encrypted at rest
- **Merchant account ID**: for providers that require one

Credentials are matched by `(provider, company, mode)`.

**Platform-wide** credentials serve all merchants (useful for small merchants).
**Per-merchant** credentials override platform ones (used when a merchant holds
their own MTN/Airtel account).

### Routing rules

Dashboard → **Providers → Routing Rules**.

Decide which provider handles each payment.

Rule fields:

| Field | Meaning |
|---|---|
| Company | `null` = platform-wide |
| Payment method | `card`, `mobile_money`, ... |
| Country | ISO2 |
| Currency | ISO3 |
| Amount range | Min/max in minor units |
| Provider | Winner |
| Fallback provider | If the winner fails |
| Priority | Lower wins |
| Traffic % | For A/B splits |

Rules are evaluated in this order:
1. Company-specific rules, sorted by priority
2. Global rules, sorted by priority
3. Any active provider matching method/currency/country

**Example rule set for Uganda:**

```
rule 1: mobile_money + UG + UGX → mtn_momo, fallback airtel_money
rule 2: card + UG → flutterwave, fallback dpo
rule 3: mobile_money + KE + KES → mpesa, fallback flutterwave
```

---

## Fee schedules

Dashboard → **Fee Schedules** (once built).

A fee schedule is a named rate card assigned to companies.

### Structure

```
Fee Schedule: "Standard Uganda"
  ├── Rule: processing, mobile_money, UGX, UG → 2.5% + 500 (min 300, max 10000)
  ├── Rule: processing, card, UGX, UG → 3.5% + 200
  └── Rule: refund, UGX → 0% + 1000
```

### Fields

| Field | Meaning |
|---|---|
| `fee_type` | `processing`, `refund`, `chargeback`, `payout`, `fx`, `international_card`, `monthly` |
| `payment_method` | Restrict to a method (null = any) |
| `currency`, `country_code`, `card_brand` | Narrows the rule |
| `percentage` | 2.5 = 2.5% |
| `fixed_amount` | Flat addition in minor units |
| `minimum_fee`, `maximum_fee` | Clamp the result |
| `tax_percentage` | Tax on the fee itself (e.g. VAT on processing) |
| `priority` | Lower wins |

### Matching

When a payment succeeds, the fee engine:

1. Loads the company's assigned schedule (or the default)
2. Finds all `processing` rules that match method/currency/country/brand/amount
3. Picks the **most specific** rule (highest specificity score)
4. Computes: `(amount × percentage) + fixed_amount`, clamped to min/max
5. Records the fee in `applied_fees` and updates `payment.fee_amount`

### Assigning a schedule

Company edit modal → `fee_schedule_id`.

If no schedule assigned, the company uses the platform default
(`is_default = true`).

---

## Payments & disputes

Dashboard → **Payments**.

### The list

Filters: company, mode, status, method, provider, date range.

Columns: id, company, amount, method, provider, status, attempts, created.

### Payment detail

Click any row. You'll see:

- Status badge, mode, method
- Amount, captured, refunded
- Provider reference, acquirer reference
- Attempt timeline (every call to a provider)
- Failure code and message (if failed)
- Linked refunds and disputes (once built)

### Actions

| Action | Available when |
|---|---|
| **Retry** | status = `failed` or `requires_payment_method` |
| **Cancel** | status is `requires_*` or `processing` |
| **Refund** | status = `succeeded` (through the refunds module) |

### Disputes

Dashboard → **Disputes** (once built).

A dispute is a chargeback opened by the customer's bank.

| Status | Meaning |
|---|---|
| `needs_response` | You must submit evidence before the deadline |
| `under_review` | Bank is reviewing evidence |
| `won` | Dispute decided in your favor — funds reinstated |
| `lost` | Funds withdrawn |
| `accepted` | You chose not to fight it |

**Evidence due dates are strict.** Missing the deadline = automatic loss. The
dashboard highlights disputes due within 72 hours in red.

### Evidence types

| Field | What to submit |
|---|---|
| `receipt` | Proof of purchase |
| `shipping_documentation` | Tracking, delivery confirmation |
| `customer_communication` | Emails, chat logs |
| `refund_policy` | Your published policy |
| `service_date` | When the service was delivered |
| `cancellation_policy` | For subscriptions |

---

## Payouts

Dashboard → **Payouts** (once built).

A payout is a batch of captured funds sent to a merchant's bank account.

### Lifecycle

```
pending → approved → processing → in_transit → paid
                                            ↘ failed
```

- **pending** — created, waiting for approval (if `requires_approval`)
- **approved** — approved by an operator, queued for the bank
- **processing** — submission in progress
- **in_transit** — sent to the bank
- **paid** — bank confirmed
- **failed** — bank rejected; balance is restored

### Creating a payout

**Automatic** — the scheduled payout job runs daily (per company's `payout_schedule`).
It bundles all `available` balance transactions into one payout.

**Manual** — for one-off payouts. Requires a bank account and available balance.

### Reserve

Companies with `reserve_percent > 0` have a rolling reserve. A percentage of
each payment is held for `reserve_hold_days` and released automatically.
This shows in `balance.reserved_amount`.

### Payout delay

`companies.payout_delay_days` determines when captured funds become withdrawable.
Default 2 days. During the delay, funds sit in `balance.pending_amount`.

A scheduled job (`MaturePendingBalancesJob`) sweeps matured pending funds to
available every hour.

---

## Risk

Dashboard → **Risk** (once built).

Three components:

### Risk rules

Configurable rules that score or block payments.

Example rule:

```json
{
  "name": "Block amounts over 5M UGX",
  "scope": "payment",
  "conditions": [
    { "field": "amount", "operator": "gt", "value": 5000000 },
    { "field": "currency", "operator": "eq", "value": "UGX" }
  ],
  "action": "block",
  "score_weight": 100,
  "priority": 10
}
```

Operators: `eq`, `ne`, `gt`, `gte`, `lt`, `lte`, `in`, `not_in`, `contains`,
`starts_with`, `is_null`, `not_null`.

Actions: `allow`, `review`, `block`, `require_3ds`, `challenge`.

### Blocklist

Emails, IPs, card fingerprints, phone numbers, countries. Two scopes:

- **Platform-wide** — blocks across every merchant
- **Merchant-scoped** — only for one company

Lists: `block`, `allow`, `review`.

### Velocity limits

Examples:

- Max 3 payments per hour per customer
- Max 100k UGX per day per customer
- Max 10 payments per minute per IP

Actions: `block` or `review`.

### Assessment queue

Every payment runs through risk assessment. Results appear in
`risk_assessments`. Elevated or blocked outcomes show in the dashboard.

---

## Audit logs

Dashboard → **Audit Logs** (once built).

Every sensitive action is logged:

- Who (user, api key, or system)
- When
- What action (`payment.refunded`, `company.suspended`, `api_key.revoked`)
- Old values → new values
- IP, user agent

Filter by company, action, user, date. Sensitive entries are flagged.

**Never delete audit logs.** They're your legal defence.

---

## Reference data

Dashboard → **Reference Data**.

Three tables used across the platform:

### Countries

Every country with: ISO2, ISO3, name, region, currency, and Stardena's
market posture (supported, collections, payouts, high risk, sanctioned).

When adding a country, set the flags carefully:

- `is_supported` — can merchants from this country onboard?
- `collections_enabled` — can we take money here?
- `payouts_enabled` — can we send money here?
- `is_high_risk` — FATF grey list or similar
- `is_sanctioned` — OFAC/UN sanctioned (auto-blocks all other flags)

### Currencies

ISO 4217 currencies with: code, name, symbol, exponent (2 for USD, 0 for UGX),
settlement/presentment flags, min/max charge amounts.

**Zero-decimal currencies** (`exponent = 0`, `is_zero_decimal = true`):
UGX, RWF, BIF, XOF, XAF, JPY, KRW, VND, CLP, ISK, XPF.

### Exchange rates

Historical and current FX pairs. Each row has:

- Base currency, quote currency
- Rate
- Markup % (your spread)
- Effective rate (rate × (1 + markup))
- Provider (oanda, ecb, internal)
- Effective window (from/to)

Rate is `1 base = N quote`. Effective date ranges let you keep history.

---

## Users & roles

Dashboard → **User Management**.

### Roles

| Role | Purpose |
|---|---|
| `super_admin` | Full access. Bypasses all permission checks. |
| `platform_admin` | Runs the platform. Cannot touch reference data or provider secrets. |
| `compliance_officer` | Reviews KYB, manages risk and disputes. Cannot move money. |
| `merchant_admin` | Full control of their own company. |
| `merchant_operator` | Day-to-day operations. No team management, no API keys. |
| `merchant_viewer` | Read-only for accountants. |

### Permissions

~155 granular permissions grouped by module. Assigned to roles via the
Roles screen. Individual users can be granted extra permissions directly
(through the Users screen).

### Impersonation

Super admins can impersonate any user to debug their experience. Every
impersonation is audit-logged.

---

## Common tasks

### Onboard a new merchant

1. Companies → Add Company. Fill in legal details.
2. Company → Compliance tab → Add representatives.
3. Company → Compliance → Upload documents.
4. Approve each document as you verify it.
5. Company → Compliance → Run verification checks.
6. Company → Change Status → In Review.
7. When all checks pass, edit company → set `kyb_status = verified`.
8. Change Status → Active.
9. Assign a Fee Schedule.
10. Set `payout_schedule` and `payout_delay_days`.

### Suspend a merchant

1. Companies → [company] → Change Status → Suspended.
2. Enter a reason.
3. All payment rails disable immediately.
4. Fund any outstanding payouts first if that's the right thing.

### Issue a refund

1. Payments → find the payment.
2. Open the detail modal.
3. **Refund** button.
4. Enter amount and reason.
5. Confirm.

The refund is queued to the provider. When it succeeds, the ledger and balance
update automatically.

### Handle a dispute

1. Disputes → filter by `needs_response`.
2. Open the dispute.
3. Submit evidence for each required field.
4. Before the deadline, click **Submit to bank**.
5. Track the outcome as it updates.

### Retry a failed payment

1. Payments → filter by `failed`.
2. Open the payment detail.
3. Click **Retry**. Optionally enable fallback provider.
4. A new attempt is created. Watch the attempt timeline.

### Add a new country

1. Reference Data → Countries.
2. Add the country with ISO codes, currency, region.
3. Set market flags carefully.
4. Add any local payment methods to its `supported_payment_methods`.
5. Add a routing rule for its currency in Providers → Routing Rules.

### Add a new provider

1. Providers → Add Provider.
2. Fill in code, name, type, supported countries/currencies/methods.
3. Save.
4. Add credentials (platform-wide or per-merchant).
5. Add a routing rule targeting it.

### Create a payment link for a merchant

1. Companies → [company] → Payment Links.
2. New Link.
3. Fill: title, amount type, currency, amount, expiry.
4. Copy the URL. Share it.
5. Watch the payments come in.

---

## Support

- Platform issues: escalate to your engineering team
- Provider issues: contact the provider's support (MTN, Airtel, Flutterwave...)
- Suspicious activity: freeze the company, escalate to compliance