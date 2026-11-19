# Runbook — Day-to-Day Operations

## Daily

### Morning check

1. **Dashboard** — any red health badges?
2. **Payments** — filter `failed`, look for spikes
3. **Disputes** — filter `needs_response`, sort by `evidence_due_by`
4. **Payouts** — any failed overnight?
5. **Providers** — any `down` or `degraded`?

### Afternoon check

1. **Disputes due tomorrow** — submit any missing evidence
2. **New companies in `pending`** — reach out if they haven't submitted KYB
3. **Notifications** — anything unread?
4. **Audit logs** — any suspicious `api_key.revoked` events?

## Weekly

1. **Payout runs** — verify all scheduled payouts succeeded
2. **Reconciliation** — check provider settlements match your internal records
3. **Ledger drift** — run the nightly check manually, review any alerts
4. **Failed webhooks** — any endpoints auto-disabled?

## Incidents

### A provider goes down

1. **Providers → [provider] → Disable**. Traffic routes to fallback.
2. Post an announcement: **Announcements → New → Incident**.
3. Contact the provider.
4. When back, re-enable and verify with a test charge.

### A merchant is compromised

1. **Companies → [company] → Suspend** with reason "Account compromise — under investigation".
2. **Users → filter by company → Deactivate** all their users.
3. **Providers → find their API keys → Revoke**.
4. **Webhooks → find their endpoints → Delete**.
5. **Payments → filter by company → refund if fraudulent**.
6. Write an incident report.

### A dispute wave

1. **Disputes → filter by `needs_response`**.
2. Group by `reason`. If `fraudulent` on a merchant, review their account.
3. If a specific card BIN is involved, **Blocklist → Add card_bin**.
4. If a pattern is clear, add a **Risk Rule** to auto-flag future payments.

### Balance is negative

Negative balance means the merchant owes the platform (refunds exceeded sales).

1. **Companies → [company]** — check recent activity.
2. **Payouts → disable automatic payouts** for that company.
3. **Notify the merchant** — they need to top up.
4. **Balance Adjustments** — if the platform is covering it, record a manual adjustment with a reason.
5. If it's a systemic issue (a bug), escalate.

### Ledger drift detected

The nightly reconciliation job alerts when `ledger_accounts.balance` doesn't
match the sum of `ledger_entries`.

1. **Do not delete anything.** The ledger is append-only.
2. Identify the affected account.
3. Compute the true balance from entries.
4. Post a **Balance Adjustment** for the difference, with reason `correction`.
5. Investigate why the drift happened.

## Escalation

| Severity | When | Contact |
|---|---|---|
| P1 | Payments down, data loss risk | Engineering on-call |
| P2 | Provider degraded, big merchant blocked | Engineering + provider support |
| P3 | Single company issue | Handle it |
| P4 | Cosmetic, no money involved | Log it, fix when convenient |

## Contacts

- **Engineering on-call:** `#eng-oncall`
- **Compliance:** `compliance@your-domain.com`
- **MTN support:** momodeveloper.mtn.com/support
- **Airtel support:** developers.airtel.africa/support
- **Flutterwave:** support@flutterwavego.com