C:\xampp\htdocs\Stardena\Pay>php artisan tinker
Psy Shell v0.12.23 (PHP 8.2.12 — cli) by Justin Hileman
New PHP manual is available (latest: 3.0.1). Update with `doc --update-manual`
> $key = \App\Models\Company\ApiKey::generate('test', 'secret');

= [
    "plaintext" => "sk_test_vab0JGxN9tvKRgdKtn0f8mGE4LPRqyjM",
    "attributes" => [
      "mode" => "test",
      "type" => "secret",
      "key_prefix" => "sk_test_",
      "key_hash" => "59a7b2d232b28df51984b12fa082ba823cf58077c557ab7b0bc7ca526b9dfd20",
      "last_four" => "qyjM",
    ],
  ]

> $company = \App\Models\Company\Company::first();

= App\Models\Company\Company {#8410
    id: 1,
    uuid: "e892459e-21fe-4fa7-8d3a-2f04f4752a4f",
    public_id: "acct_qsnqksetggjuri4dt9sem23c",
    name: "Stardena Demo Store",
    legal_name: "Stardena Demo Store Ltd",
    slug: "stardena-demo",
    email: "hello@stardena-demo.test",
    support_email: "support@stardena-demo.test",
    support_phone: "+256700000100",
    website: "https://stardena-demo.test",
    logo_path: null,
    brand_color: "#6E3FE7",
    country_id: 1,
    business_type: "company",
    industry: "E-commerce",
    mcc: "5999",
    registration_number: "UG-REG-123456",
    tax_identification_number: "TIN-100200300",
    incorporated_on: "2024-09-21",
    address_line1: "Plot 10, Kampala Road",
    address_line2: null,
    city: "Kampala",
    state: "Central",
    postal_code: "256",
    default_currency: "UGX",
    settlement_currency: "UGX",
    timezone: "Africa/Kampala",
    statement_descriptor: "STARDENA DEMO",
    status: "active",
    kyb_status: "verified",
    live_mode_enabled: 1,
    charges_enabled: 1,
    payouts_enabled: 1,
    onboarding_step: null,
    requirements_due: null,
    activated_at: "2026-08-22 10:27:59",
    suspended_at: null,
    suspension_reason: null,
    fee_schedule_id: null,
    risk_level: "standard",
    risk_score: 20,
    payout_schedule: "daily",
    payout_delay_days: 2,
    reserve_percent: "0.00",
    reserve_hold_days: 0,
    owner_id: 5,
    #settings: null,
    #metadata: null,
    created_at: "2026-09-21 10:27:59",
    updated_at: "2026-09-23 00:06:55",
    deleted_at: null,
  }

> $apiKey = $company->apiKeys()->create(array_merge($key['attributes'], [
.     'name' => 'Test Secret Key',
.     'created_by_id' => 1,
.     'scopes' => null,
. ]));

= App\Models\Company\ApiKey {#8985
    mode: "test",
    type: "secret",
    key_prefix: "sk_test_",
    #key_hash: "59a7b2d232b28df51984b12fa082ba823cf58077c557ab7b0bc7ca526b9dfd20",
    last_four: "qyjM",
    name: "Test Secret Key",
    created_by_id: 1,
    scopes: null,
    company_id: 1,
    uuid: "d26477c2-4332-4d81-8237-c55be5330205",
    updated_at: "2026-09-23 00:42:36",
    created_at: "2026-09-23 00:42:36",
    id: 3,
  }

>                                                                                                                       



C:\xampp\htdocs\Stardena\Pay>curl -X POST http://127.0.0.1:8000/api/v1/payments -H "Authorization: Bearer sk_test_vab0JGxN9tvKRgdKtn0f8mGE4LPRqyjM" -H "Content-Type: application/json" -H "Idempotency-Key: test-001" -d "{\"amount\":50000,\"currency\":\"UGX\",\"payment_method_type\":\"mobile_money\",\"customer_email\":\"test@example.com\",\"customer_country\":\"UG\",\"reference\":\"api-test-001\"}"
{"id":"pay_6grz6tctl9ipwq1ihoevufai","object":"payment","status":"succeeded","amount":50000,"amount_captured":50000,"amount_refunded":0,"currency":"UGX","payment_method_type":"mobile_money","customer_id":null,"customer_email":null,"reference":"api-test-001","description":null,"metadata":{},"created":"2026-09-23T00:43:50+00:00"}
C:\xampp\htdocs\Stardena\Pay>


Test idempotency
C:\xampp\htdocs\Stardena\Pay>curl -X POST http://127.0.0.1:8000/api/v1/payments -H "Authorization: Bearer sk_test_vab0JGxN9tvKRgdKtn0f8mGE4LPRqyjM" -H "Content-Type: application/json" -H "Idempotency-Key: test-001" -d "{\"amount\":50000,\"currency\":\"UGX\",\"payment_method_type\":\"mobile_money\",\"customer_email\":\"test@example.com\",\"customer_country\":\"UG\",\"reference\":\"api-test-001\"}"
{"id":"pay_6grz6tctl9ipwq1ihoevufai","object":"payment","status":"succeeded","amount":50000,"amount_captured":50000,"amount_refunded":0,"currency":"UGX","payment_method_type":"mobile_money","customer_id":null,"customer_email":null,"reference":"api-test-001","description":null,"metadata":[],"created":"2026-09-23T00:43:50+00:00"}
C:\xampp\htdocs\Stardena\Pay>


C:\xampp\htdocs\Stardena\Pay>curl -i -X POST http://127.0.0.1:8000/api/v1/payments -H "Authorization: Bearer sk_test_vab0JGxN9tvKRgdKtn0f8mGE4LPRqyjM" -H "Content-Type: application/json" -H "Idempotency-Key: test-001" -d "{\"amount\":50000,\"currency\":\"UGX\",\"payment_method_type\":\"mobile_money\",\"customer_email\":\"test@example.com\",\"customer_country\":\"UG\",\"reference\":\"api-test-001\"}"
HTTP/1.1 200 OK
Host: 127.0.0.1:8000
Connection: close
X-Powered-By: PHP/8.2.12
Cache-Control: no-cache, private
Date: Wed, 23 Sep 2026 00:46:27 GMT
Content-Type: application/json
Idempotency-Replayed: true
X-Request-Id: 7c89abe5-2647-436f-ba0b-2dbacf22dce8
X-RateLimit-Limit: 600
X-RateLimit-Remaining: 598
Access-Control-Allow-Origin: *

{"id":"pay_6grz6tctl9ipwq1ihoevufai","object":"payment","status":"succeeded","amount":50000,"amount_captured":50000,"amount_refunded":0,"currency":"UGX","payment_method_type":"mobile_money","customer_id":null,"customer_email":null,"reference":"api-test-001","description":null,"metadata":[],"created":"2026-09-23T00:43:50+00:00"}
C:\xampp\htdocs\Stardena\Pay>