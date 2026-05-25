<?php

namespace Laraditz\Xendit\Models;

use Illuminate\Database\Eloquent\Model;
use Laraditz\Xendit\Enums\WebhookStatus;

class XenditWebhookLog extends Model
{
    protected $table = 'xendit_webhook_logs';

    protected $fillable = [
        'event_type',
        'external_id',
        'xendit_id',
        'payload',
        'status',
        'error_message',
        'retry_count',
        'processed_at',
    ];

    protected $casts = [
        'status' => WebhookStatus::class,
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, WebhookStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope to filter processed webhooks
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', WebhookStatus::Processed->value);
    }

    /**
     * Scope to filter pending webhooks
     */
    public function scopePending($query)
    {
        return $query->where('status', WebhookStatus::Pending->value);
    }

    /**
     * Scope to filter failed webhooks
     */
    public function scopeFailed($query)
    {
        return $query->where('status', WebhookStatus::Failed->value);
    }

    /**
     * Scope to filter by event type
     */
    public function scopeEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by external ID
     */
    public function scopeExternalId($query, string $externalId)
    {
        return $query->where('external_id', $externalId);
    }

    /**
     * Check if webhook is processed
     */
    public function isProcessed(): bool
    {
        return $this->status->isProcessed();
    }

    /**
     * Check if webhook can be retried
     */
    public function canRetry(): bool
    {
        return $this->status->canRetry();
    }

    /**
     * Mark webhook as processed
     */
    public function markAsProcessed(): self
    {
        $this->update([
            'status' => WebhookStatus::Processed,
            'processed_at' => now(),
            'error_message' => null,
        ]);

        return $this;
    }

    /**
     * Mark webhook as failed
     */
    public function markAsFailed(string $errorMessage): self
    {
        $this->update([
            'status' => WebhookStatus::Failed,
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);

        return $this;
    }

    /**
     * Increment retry count
     */
    public function incrementRetry(): self
    {
        $this->increment('retry_count');

        return $this;
    }
}
