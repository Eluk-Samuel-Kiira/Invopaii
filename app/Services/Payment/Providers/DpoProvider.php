<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\ProviderCredential;
use Illuminate\Http\Request;

class DpoProvider extends AbstractProvider
{
    protected array $baseUrls = [
        'test' => 'https://secure.3gdirectpay.com',
        'live' => 'https://secure.3gdirectpay.com',
    ];

    public function charge(PaymentAttempt $attempt, ProviderCredential $credential): ProviderResult
    {
        $payment = $attempt->payment;

        // DPO's classic flow:
        // 1. POST /API/v6/ → createToken with CompanyToken + payment details → returns TransToken
        // 2. Redirect customer to https://secure.3gdirectpay.com/payv2.php?ID={TransToken}
        // 3. Customer authorizes
        // 4. Redirect back to your ReturnURL with TransToken in query
        // 5. POST /API/v6/ → verifyToken to confirm status
        //
        // Newer flow (Payments API v1) is JSON-based and works more like Flutterwave.

        $payload = [
            'companyToken' => $credential->merchant_account_id,     // DPO calls it "Company Token"
            'request' => 'createToken',
            'payment' => [
                'amount' => $this->toMajor($payment->amount, $payment->currency),
                'currency' => $payment->currency,
                'paymentType' => $this->mapPaymentType($payment->payment_method_type),
            ],
            'customer' => [
                'email' => $payment->receipt_email ?? $payment->customer?->email,
                'name' => $payment->customer?->name,
                'phone' => $payment->paymentMethod?->msisdn,
            ],
            'reference' => $payment->reference ?? $payment->public_id,
            'redirectURL' => $payment->return_url,
            'backURL' => $payment->return_url,
            'description' => $payment->description,
        ];

        try {
            // TODO: POST /API/v6/ with the payload above
            // $response = $this->http($attempt->mode)
            //     ->asForm()
            //     ->post('/API/v6/', $payload);
            //
            // Parse the response. DPO returns XML-ish or JSON depending on endpoint version.
            // Extract $data['TransToken'] and $data['TransRef'].
            //
            // Return ProviderResult::pending with next_action = redirect:
            //   nextActionType: 'redirect',
            //   nextAction: ['url' => 'https://secure.3gdirectpay.com/payv2.php?ID=' . $transToken],

            return ProviderResult::failure(
                'DPO integration not yet enabled. Awaiting approval.',
                'integration_pending'
            );
        } catch (\Throwable $e) {
            return ProviderResult::failure('DPO charge exception: ' . $e->getMessage(), 'provider_exception');
        }
    }

    public function verifyPayment(Payment $payment, ProviderCredential $credential): ProviderResult
    {
        $reference = $payment->provider_reference;
        if (!$reference) {
            return ProviderResult::failure('No DPO reference to verify.', 'no_reference');
        }

        // TODO: POST /API/v6/ with:
        //   request = 'verifyToken'
        //   companyToken = <merchant_account_id>
        //   transactionToken = <reference>
        //
        // Response contains Result (0 = paid, 900 = pending, etc.) and ResultExplanation.
        //
        // Map:
        //   Result "000" or "0"  → ProviderResult::success
        //   Result "900"         → ProviderResult::pending
        //   anything else        → ProviderResult::failure

        return ProviderResult::failure('DPO verify not yet enabled.', 'integration_pending');
    }

    public function refund(Payment $payment, int $amount, ProviderCredential $credential, ?string $reason = null): ProviderResult
    {
        // TODO: POST /API/v6/ with:
        //   request = 'refundToken'
        //   companyToken = <merchant_account_id>
        //   transactionToken = <reference>
        //   refundAmount = <amount>
        //   refundDetails = <reason>

        return ProviderResult::failure('DPO refunds not yet enabled.', 'integration_pending');
    }

    public function verifyWebhook(Request $request, ProviderCredential $credential): array
    {
        // DPO's older flow doesn't sign webhooks — it redirects back with query params.
        // The newer Payments API v1 supports webhooks with an MD5 signature.
        //
        // Old flow (redirect-based):
        //   Customer returns to returnURL?TransToken=xxx&CCDapproval=xxx
        //   You must call verifyToken to confirm.
        //
        // New flow (webhook):
        //   POST body has { CompanyRef, TransactionToken, Result, ResultExplanation }
        //   Signature header: X-DPO-Signature (MD5 of specific fields concatenated with secret)
        //
        // TODO: implement MD5 signature check once you have API docs from DPO.

        $payload = $request->all();
        $reference = $payload['TransactionToken'] ?? $payload['TransToken'] ?? null;
        $result = (string) ($payload['Result'] ?? $payload['CCDapproval'] ?? '');

        $eventType = match ($result) {
            '0', '000', '900' => 'payment.succeeded',
            '901', '902', '903' => 'payment.failed',
            '904' => 'payment.cancelled',
            default => 'payment.updated',
        };

        return [
            'valid' => true, // MD5 check to be implemented
            'event_type' => $eventType,
            'provider_event_id' => $reference . ':' . $result,
            'provider_reference' => $reference,
            'payload' => $payload,
        ];
    }

    protected function mapPaymentType(?string $methodType): string
    {
        return match ($methodType) {
            'card' => 'CC',
            'mobile_money' => 'MPESA', // DPO calls it this generically
            'bank_transfer' => 'BT',
            default => 'CC',
        };
    }

    protected function toMajor(int $minor, string $currency): float
    {
        return in_array($currency, ['UGX', 'RWF', 'TZS'], true)
            ? (float) $minor
            : round($minor / 100, 2);
    }
}