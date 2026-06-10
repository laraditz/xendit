<?php

namespace Laraditz\Xendit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laraditz\Xendit\Enums\SettlementStatus;
use Laraditz\Xendit\Enums\TransactionStatus;
use Laraditz\Xendit\Enums\TransactionType;
use Laraditz\Xendit\Events\TransactionSettled;

class XenditTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'xendit_transactions';

    protected $fillable = [
        'payment_id',
        'transaction_id',
        'reference_id',
        'type',
        'amount',
        'currency',
        'status',
        'payment_method',
        'fee',
        'net_amount',
        'settlement_status',
        'raw_response',
        'completed_at',
        'settled_at',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'settlement_status' => SettlementStatus::class,
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'raw_response' => 'array',
        'completed_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    /**
     * Transaction belongs to a payment
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(XenditPayment::class, 'payment_id');
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, TransactionStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope to filter successful transactions
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', TransactionStatus::Success->value);
    }

    /**
     * Scope to filter by type
     */
    public function scopeType($query, TransactionType $type)
    {
        return $query->where('type', $type->value);
    }

    /**
     * Scope to filter payments only
     */
    public function scopePayments($query)
    {
        return $query->where('type', TransactionType::Payment->value);
    }

    /**
     * Scope to filter refunds only
     */
    public function scopeRefunds($query)
    {
        return $query->where('type', TransactionType::Refund->value);
    }

    /**
     * Scope to filter by settlement status
     */
    public function scopeSettlementStatus($query, SettlementStatus $status)
    {
        return $query->where('settlement_status', $status->value);
    }

    /**
     * Scope to filter settled transactions (Settled or EarlySettled)
     */
    public function scopeSettled($query)
    {
        return $query->whereIn('settlement_status', [
            SettlementStatus::Settled->value,
            SettlementStatus::EarlySettled->value,
        ]);
    }

    /**
     * Scope to filter transactions pending settlement
     */
    public function scopePendingSettlement($query)
    {
        return $query->where('settlement_status', SettlementStatus::Pending->value);
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccess(): bool
    {
        return $this->status->isSuccess();
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    /**
     * Check if transaction is settled
     */
    public function isSettled(): bool
    {
        return $this->settlement_status?->isSettled() ?? false;
    }

    /**
     * Check if transaction is pending settlement
     */
    public function isPendingSettlement(): bool
    {
        return $this->settlement_status === SettlementStatus::Pending;
    }

    /**
     * Mark transaction as success
     */
    public function markAsSuccess(): self
    {
        $this->update([
            'status' => TransactionStatus::Success,
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(): self
    {
        $this->update([
            'status' => TransactionStatus::Failed,
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark transaction as settled
     */
    public function markAsSettled(SettlementStatus $status = SettlementStatus::Settled, string|\DateTimeInterface|null $settledAt = null): self
    {
        $this->update([
            'settlement_status' => $status,
            'settled_at' => $settledAt ?? now(),
        ]);

        event(new TransactionSettled($this));

        return $this;
    }
}
