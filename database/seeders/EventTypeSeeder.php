<?php

namespace Database\Seeders;

use App\Models\Webhook\EventType;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // ── Payments ──
            ['payment.created',              'payment', 'A payment intent was created',                     'payments'],
            ['payment.processing',           'payment', 'Payment is being processed by a provider',         'payments'],
            ['payment.requires_action',      'payment', 'Customer action required (3DS, etc.)',             'payments'],
            ['payment.succeeded',            'payment', 'Payment was successfully captured',                'payments'],
            ['payment.failed',               'payment', 'Payment attempt failed',                           'payments'],
            ['payment.canceled',             'payment', 'Payment was canceled before completion',           'payments'],
            ['payment.refunded',             'payment', 'Payment was fully refunded',                       'payments'],
            ['payment.partially_refunded',   'payment', 'Payment was partially refunded',                   'payments'],
            ['payment.disputed',             'payment', 'A chargeback or dispute was opened',               'payments'],

            // ── Refunds ──
            ['refund.created',               'refund',  'A refund was created',                             'refunds'],
            ['refund.succeeded',             'refund',  'Refund completed successfully',                    'refunds'],
            ['refund.failed',                'refund',  'Refund failed',                                    'refunds'],

            // ── Payouts ──
            ['payout.created',               'payout',  'A payout was requested',                           'payouts'],
            ['payout.processing',            'payout',  'Payout is being processed',                        'payouts'],
            ['payout.paid',                  'payout',  'Payout was settled to the merchant bank',          'payouts'],
            ['payout.failed',                'payout',  'Payout failed',                                    'payouts'],
            ['payout.canceled',              'payout',  'Payout was canceled',                              'payouts'],

            // ── Invoices ──
            ['invoice.created',              'invoice', 'Invoice was created',                              'invoices'],
            ['invoice.sent',                 'invoice', 'Invoice was sent to the customer',                 'invoices'],
            ['invoice.paid',                 'invoice', 'Invoice was paid',                                 'invoices'],
            ['invoice.payment_failed',       'invoice', 'Invoice payment failed',                           'invoices'],
            ['invoice.voided',               'invoice', 'Invoice was voided',                               'invoices'],
            ['invoice.overdue',              'invoice', 'Invoice became overdue',                           'invoices'],

            // ── Subscriptions ──
            ['subscription.created',         'subscription', 'Subscription was created',                    'subscriptions'],
            ['subscription.updated',         'subscription', 'Subscription was updated',                    'subscriptions'],
            ['subscription.renewed',         'subscription', 'Subscription renewed for another period',     'subscriptions'],
            ['subscription.canceled',        'subscription', 'Subscription was canceled',                   'subscriptions'],
            ['subscription.trial_ending',    'subscription', 'Trial period is ending soon',                'subscriptions'],
            ['subscription.past_due',        'subscription', 'Subscription payment is past due',           'subscriptions'],
            ['subscription.unpaid',          'subscription', 'Subscription became unpaid',                  'subscriptions'],

            // ── Customers ──
            ['customer.created',             'customer', 'Customer was created',                           'customers'],
            ['customer.updated',             'customer', 'Customer details were updated',                  'customers'],
            ['customer.deleted',             'customer', 'Customer was deleted',                           'customers'],
            ['customer.payment_method_added','customer', 'A payment method was added',                     'customers'],
            ['customer.payment_method_removed','customer', 'A payment method was removed',                 'customers'],

            // ── Disputes ──
            ['dispute.created',              'dispute', 'A dispute was opened',                            'disputes'],
            ['dispute.updated',              'dispute', 'Dispute was updated',                             'disputes'],
            ['dispute.closed',               'dispute', 'Dispute was closed',                              'disputes'],
            ['dispute.evidence_due',         'dispute', 'Evidence submission deadline approaching',        'disputes'],

            // ── Payment Links / Checkout ──
            ['payment_link.created',         'payment_link', 'Payment link was created',                   'payment_links'],
            ['payment_link.updated',         'payment_link', 'Payment link was updated',                   'payment_links'],
            ['checkout.session.completed',   'checkout_session', 'Checkout session completed',            'checkout'],
            ['checkout.session.expired',     'checkout_session', 'Checkout session expired',              'checkout'],

            // ── Balances ──
            ['balance.available_updated',    'balance', 'Available balance changed',                       'balances'],
            ['balance.transaction_created',  'balance', 'A balance transaction was created',               'balances'],

            // ── Account / Company ──
            ['account.updated',              'account', 'Company account details updated',                 'account'],
            ['account.verification_succeeded','account','KYB verification succeeded',                      'account'],
            ['account.verification_failed',  'account', 'KYB verification failed',                         'account'],
            ['account.restricted',           'account', 'Account was restricted',                          'account'],
        ];

        foreach ($types as [$name, $resource, $description, $category]) {
            EventType::updateOrCreate(
                ['name' => $name],
                [
                    'resource' => $resource,
                    'description' => $description,
                    'category' => $category,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✓ Seeded ' . count($types) . ' event types.');
    }
}