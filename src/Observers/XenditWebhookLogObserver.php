<?php

namespace Laraditz\Xendit\Observers;

use Laraditz\Xendit\Enums\WebhookStatus;
use Laraditz\Xendit\Models\XenditWebhookLog;

class XenditWebhookLogObserver
{
    /**
     * Handle the XenditWebhookLog "creating" event.
     */
    public function creating(XenditWebhookLog $webhookLog): void
    {
        // Set default status if not provided
        if (is_null($webhookLog->status)) {
            $webhookLog->status = WebhookStatus::Pending;
        }
    }
}
