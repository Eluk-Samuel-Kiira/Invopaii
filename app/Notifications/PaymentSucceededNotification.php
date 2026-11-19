<?php

namespace App\Notifications;

use App\Models\Payment\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentSucceededNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public int $feeTotal = 0
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'company_id' => $this->payment->company_id,
            'category' => 'payment',
            'severity' => 'success',
            'message' => "Payment of {$this->formatMoney($this->payment->amount)} received",
            'amount' => $this->payment->amount,
            'currency' => $this->payment->currency,
            'fee' => $this->feeTotal,
            'payment_id' => $this->payment->id,
            'public_id' => $this->payment->public_id,
            'reference' => $this->payment->reference,
            'customer' => $this->payment->customer?->name,
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    protected function formatMoney(int $minor): string
    {
        $zeroDecimal = ['UGX', 'RWF', 'BIF', 'XOF', 'XAF', 'JPY', 'KRW', 'VND', 'CLP', 'ISK', 'XPF'];
        $currency = $this->payment->currency;
        $amount = in_array($currency, $zeroDecimal, true) ? $minor : $minor / 100;

        return number_format($amount, in_array($currency, $zeroDecimal, true) ? 0 : 2) . ' ' . $currency;
    }
}