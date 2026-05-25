<?php

namespace Laraditz\Xendit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laraditz\Xendit\Enums\PaymentStatus;

class XenditPayment extends Model
{
    use SoftDeletes;

    protected $table = 'xendit_payments';

    protected $fillable = [
        'external_id',
        'xendit_id',
        'payable_id',
        'payable_type',
        'payment_type',
        'payment_channel',
        'channel_properties',
        'status',
        'amount',
        'currency',
        'description',
        'customer_id',
        'payer_id',
        'payer_email',
        'payer_name',
        'payer_phone',
        'payment_url',
        'callback_url',
        'success_redirect_url',
        'failure_redirect_url',
        'metadata',
        'payment_details',
        'for_user_id',
        'split_rule_id',
        'paid_at',
        'expired_at',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'payment_details' => 'array',
        'channel_properties' => 'json',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    /**
     * Polymorphic relationship to any model
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Payment has many transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(XenditTransaction::class, 'payment_id');
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, PaymentStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope to filter paid payments
     */
    public function scopePaid($query)
    {
        return $query->where('status', PaymentStatus::Paid->value);
    }

    /**
     * Scope to filter pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::Pending->value);
    }

    /**
     * Scope to filter by payment type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('payment_type', $type);
    }

    /**
     * Scope to filter by external ID
     */
    public function scopeExternalId($query, string $externalId)
    {
        return $query->where('external_id', $externalId);
    }

    /**
     * Scope to filter by Xendit ID
     */
    public function scopeXenditId($query, string $xenditId)
    {
        return $query->where('xendit_id', $xenditId);
    }

    public function scopeForUserId($query, string $userId)
    {
        return $query->where('for_user_id', $userId);
    }

    public function scopeSplitRuleId($query, string $ruleId)
    {
        return $query->where('split_rule_id', $ruleId);
    }

    /**
     * Check if payment is paid
     */
    public function isPaid(): bool
    {
        return $this->status->isPaid();
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    /**
     * Check if payment is in final state
     */
    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    /**
     * Mark payment as paid
     */
    public function markAsPaid(?string $xenditId = null): self
    {
        $updateData = [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ];

        if ($xenditId) {
            $updateData = [
                ...$updateData,
                'xendit_id' => $xenditId ?? $this->xendit_id,
            ];
        }

        $this->update($updateData);

        return $this;
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(): self
    {
        $this->update([
            'status' => PaymentStatus::Failed,
        ]);

        return $this;
    }

    /**
     * Mark payment as expired
     */
    public function markAsExpired(): self
    {
        $this->update([
            'status' => PaymentStatus::Expired,
        ]);

        return $this;
    }

    /**
     * Mark payment as cancelled
     */
    public function markAsCancelled(): self
    {
        $this->update([
            'status' => PaymentStatus::Cancelled,
        ]);

        return $this;
    }
}
