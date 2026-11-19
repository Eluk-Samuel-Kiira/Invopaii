<?php

use App\Models\Company\Company;
use App\Models\Webhook\WebhookEndpoint;
use App\Models\Webhook\Event;
use App\Models\Webhook\WebhookDelivery;
use App\Services\Payment\PaymentIntentService;

$company = Company::first();
echo "Company: {$company->name} ({$company->public_id})\n\n";

// Step 1 — list existing endpoints
$endpoints = WebhookEndpoint::where('company_id', $company->id)
    ->where('mode', 'test')
    ->where('status', 'enabled')
    ->get();

echo "Active endpoints: {$endpoints->count()}\n";
foreach ($endpoints as $e) {
    echo "  #{$e->id} — {$e->url}\n";
}

// Step 2 — delete extras, keep only the newest
if ($endpoints->count() > 1) {
    $keep = $endpoints->sortByDesc('id')->first();
    echo "\nKeeping only endpoint #{$keep->id}, deleting the rest...\n";
    WebhookEndpoint::where('company_id', $company->id)
        ->where('id', '!=', $keep->id)
        ->delete();
    $endpoints = collect([$keep]);
}

$endpoint = $endpoints->first();
echo "\nUsing endpoint #{$endpoint->id}\n";
echo "Secret: {$endpoint->secret}\n\n";

// Step 3 — fire a payment
$data = [
    'mode' => 'test',
    'source' => 'api',
    'customer_id' => $company->customers()->first()->id,
    'currency' => 'UGX',
    'amount' => 25000,
    'payment_method_type' => 'mobile_money',
    'customer_country' => 'UG',
    'reference' => 'wh-fresh-' . time(),
];

$payment = PaymentIntentService::createAndProcess($company, $data);
echo "Payment: {$payment->public_id}\n";
echo "Status:  {$payment->status}\n\n";

// Step 4 — the event
$event = Event::where('resource_public_id', $payment->public_id)
    ->where('type', 'payment.succeeded')
    ->first();

if (!$event) {
    echo "❌ No event created. Check PaymentProcessor::handleSuccess() wiring.\n";
    return;
}

echo "Event:           {$event->public_id}\n";
echo "Type:            {$event->type}\n";
echo "Mode:            {$event->mode}\n";
echo "Pending hooks:   {$event->pending_webhooks}\n\n";

// Step 5 — the delivery
$delivery = $event->deliveries()->first();

if (!$delivery) {
    echo "❌ No delivery created. Check EventDispatcher endpoint matching.\n";
    return;
}

echo "Delivery:        {$delivery->uuid}\n";
echo "Status:          {$delivery->status}\n";
echo "Attempt:         {$delivery->attempt}\n";
echo "Endpoint:        #{$delivery->webhook_endpoint_id}\n\n";

if ($delivery->status === 'pending') {
    echo "⚠ Delivery still pending — is `php artisan queue:work` running?\n";
    echo "  Or run: php artisan queue:work --once\n";
}

if ($delivery->status === 'failed' || $delivery->status === 'abandoned') {
    echo "❌ Delivery failed.\n";
    echo "Response code: {$delivery->response_code}\n";
    echo "Error: {$delivery->error_message}\n";
}




// Instructions

// 1. Merchant action (API call, dashboard, subscription job)
//    ↓
// 2. PaymentIntentService::create()       → creates `payments` row
//    ↓
// 3. PaymentProcessor::process()          → routes to a provider, creates `payment_attempts`
//    ↓
// 4. Provider charges                     → returns ProviderResult
//    ↓
// 5. PaymentProcessor::handleSuccess()    → fires ALL the post-payment side effects
//    ↓
// 6. FeeService::applyProcessingFee()     → writes `applied_fees`, sets payment.fee_amount
//    ↓
// 7. LedgerService::post()                → writes `ledger_transactions` + `ledger_entries`
//    ↓
// 8. creditMerchantBalance()              → updates `balances`, writes `balance_transactions`
//    ↓
// 9. AuditLog::record()                   → writes `audit_logs`
//    ↓
// 10. notifyPaymentSucceeded()            → writes `notifications`
//     ↓
// 11. EventDispatcher::dispatch()         → writes `events`, fans out to `webhook_deliveries`
//     ↓
// 12. DeliverWebhook job (queued)         → signs the payload, POSTs to the endpoint URL
//     ↓
// 13. Merchant's server receives it       → verifies signature, updates their system