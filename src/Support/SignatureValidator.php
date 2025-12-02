<?php

namespace Laraditz\Xendit\Support;

use Laraditz\Xendit\Exceptions\WebhookException;

class SignatureValidator
{
    /**
     * Verify Xendit webhook signature
     */
    public function verify(string $webhookToken, string $payload): bool
    {
        $secret = config('xendit.webhook_secret');

        if (empty($secret)) {
            throw new WebhookException('Xendit webhook secret is not configured.');
        }

        // Xendit uses webhook verification token in the header
        return hash_equals($secret, $webhookToken);
    }

    /**
     * Verify callback token from Xendit
     */
    public function verifyCallbackToken(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $secret = config('xendit.webhook_secret');

        if (empty($secret)) {
            throw new WebhookException('Xendit webhook secret is not configured.');
        }

        return hash_equals($secret, $token);
    }
}
