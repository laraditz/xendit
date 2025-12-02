<?php

namespace Laraditz\Xendit\Support;

use Laraditz\Xendit\Events\PaymentExpired;
use Laraditz\Xendit\Events\PaymentFailed;
use Laraditz\Xendit\Events\PaymentPaid;
use Laraditz\Xendit\Events\PaymentTokenActivated;
use Laraditz\Xendit\Events\PaymentTokenCreated;
use Laraditz\Xendit\Events\RefundCreated;
use Laraditz\Xendit\Events\RefundSucceeded;
use Laraditz\Xendit\Events\WebhookReceived;
use Laraditz\Xendit\Models\XenditPayment;
use Laraditz\Xendit\Models\XenditWebhookLog;

class WebhookHandler
{
    /**
     * Handle incoming webhook
     */
    public function handle(array $payload): void
    {
        // Log the webhook
        $webhookLog = $this->logWebhook($payload);

        try {
            // Dispatch generic webhook received event
            event(new WebhookReceived($this->getEventType($payload), $payload));

            // Process based on event type
            $this->processWebhook($payload);

            // Mark webhook as processed
            $webhookLog->markAsProcessed();
        } catch (\Exception $e) {
            // Mark webhook as failed
            $webhookLog->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Log webhook to database
     */
    protected function logWebhook(array $payload): XenditWebhookLog
    {
        return XenditWebhookLog::create([
            'event_type' => $this->getEventType($payload),
            'external_id' => $payload['external_id'] ?? null,
            'xendit_id' => $payload['id'] ?? null,
            'payload' => $payload,
        ]);
    }

    /**
     * Process webhook based on type
     */
    protected function processWebhook(array $payload): void
    {
        $eventType = $this->getEventType($payload);

        match (true) {
            // Payment webhooks
            str_contains($eventType, 'payment.succeeded') || str_contains($eventType, 'payment.paid') => $this->handlePaymentSuccess($payload),
            str_contains($eventType, 'payment.failed') => $this->handlePaymentFailed($payload),
            str_contains($eventType, 'payment.expired') => $this->handlePaymentExpired($payload),

            // Payment Token webhooks
            str_contains($eventType, 'payment_token.created') => $this->handlePaymentTokenCreated($payload),
            str_contains($eventType, 'payment_token.activated') => $this->handlePaymentTokenActivated($payload),

            // Refund webhooks
            str_contains($eventType, 'refund.created') => $this->handleRefundCreated($payload),
            str_contains($eventType, 'refund.succeeded') => $this->handleRefundSucceeded($payload),

            // Legacy webhooks (kept for backwards compatibility)
            str_contains($eventType, 'invoice.paid') => $this->handleInvoicePaid($payload),
            str_contains($eventType, 'invoice.expired') => $this->handleInvoiceExpired($payload),
            str_contains($eventType, 'ewallet') && str_contains($eventType, 'succeeded') => $this->handleEwalletSuccess($payload),
            str_contains($eventType, 'ewallet') && str_contains($eventType, 'failed') => $this->handleEwalletFailed($payload),
            str_contains($eventType, 'virtual_account') && str_contains($eventType, 'paid') => $this->handleVirtualAccountPaid($payload),
            str_contains($eventType, 'qr') && str_contains($eventType, 'paid') => $this->handleQrCodePaid($payload),

            default => null, // Unknown event type
        };
    }

    /**
     * Handle invoice paid webhook
     */
    protected function handleInvoicePaid(array $payload): void
    {
        $payment = XenditPayment::externalId($payload['external_id'])->first();

        if ($payment) {
            $payment->markAsPaid($payload['id']);
            event(new PaymentPaid($payment));
        }
    }

    /**
     * Handle invoice expired webhook
     */
    protected function handleInvoiceExpired(array $payload): void
    {
        $payment = XenditPayment::externalId($payload['external_id'])->first();

        if ($payment) {
            $payment->markAsExpired();
            event(new PaymentExpired($payment));
        }
    }

    /**
     * Handle e-wallet success webhook
     */
    protected function handleEwalletSuccess(array $payload): void
    {
        $payment = XenditPayment::externalId($payload['reference_id'] ?? $payload['external_id'])->first();

        if ($payment) {
            $payment->markAsPaid($payload['id']);
            event(new PaymentPaid($payment));
        }
    }

    /**
     * Handle e-wallet failed webhook
     */
    protected function handleEwalletFailed(array $payload): void
    {
        $payment = XenditPayment::externalId($payload['reference_id'] ?? $payload['external_id'])->first();

        if ($payment) {
            $payment->markAsFailed();
            event(new PaymentFailed($payment));
        }
    }

    /**
     * Handle virtual account paid webhook
     */
    protected function handleVirtualAccountPaid(array $payload): void
    {
        $payment = XenditPayment::externalId($payload['external_id'])->first();

        if ($payment) {
            $payment->markAsPaid($payload['id']);
            event(new PaymentPaid($payment));
        }
    }

    /**
     * Handle QR code paid webhook
     */
    protected function handleQrCodePaid(array $payload): void
    {
        $payment = XenditPayment::externalId($payload['external_id'])->first();

        if ($payment) {
            $payment->markAsPaid($payload['id']);
            event(new PaymentPaid($payment));
        }
    }

    /**
     * Handle payment success webhook (current API)
     */
    protected function handlePaymentSuccess(array $payload): void
    {
        $payment = XenditPayment::externalId($payload['reference_id'] ?? $payload['external_id'])->first();

        if ($payment) {
            $payment->markAsPaid($payload['id']);
            event(new PaymentPaid($payment));
        }
    }

    /**
     * Handle payment failed webhook (current API)
     */
    protected function handlePaymentFailed(array $payload): void
    {
        $payment = XenditPayment::externalId($payload['reference_id'] ?? $payload['external_id'])->first();

        if ($payment) {
            $payment->markAsFailed();
            event(new PaymentFailed($payment));
        }
    }

    /**
     * Handle payment expired webhook (current API)
     */
    protected function handlePaymentExpired(array $payload): void
    {
        $payment = XenditPayment::externalId($payload['reference_id'] ?? $payload['external_id'])->first();

        if ($payment) {
            $payment->markAsExpired();
            event(new PaymentExpired($payment));
        }
    }

    /**
     * Handle payment token created webhook
     */
    protected function handlePaymentTokenCreated(array $payload): void
    {
        event(new PaymentTokenCreated($payload));
    }

    /**
     * Handle payment token activated webhook
     */
    protected function handlePaymentTokenActivated(array $payload): void
    {
        event(new PaymentTokenActivated($payload));
    }

    /**
     * Handle refund created webhook
     */
    protected function handleRefundCreated(array $payload): void
    {
        event(new RefundCreated($payload));
    }

    /**
     * Handle refund succeeded webhook
     */
    protected function handleRefundSucceeded(array $payload): void
    {
        event(new RefundSucceeded($payload));
    }

    /**
     * Get event type from payload
     */
    protected function getEventType(array $payload): string
    {
        // Different Xendit webhooks have different structures
        return $payload['event'] ?? $payload['status'] ?? 'unknown';
    }
}
