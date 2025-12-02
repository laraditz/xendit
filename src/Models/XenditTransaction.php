<?php

namespace Laraditz\Xendit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laraditz\Xendit\Enums\TransactionStatus;
use Laraditz\Xendit\Enums\TransactionType;

class XenditTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'xendit_transactions';

    protected $fillable = [
        'payment_id',
        'transaction_id',
        'type',
        'amount',
        'currency',
        'status',
        'payment_method',
        'fee',
        'net_amount',
        'raw_response',
        'completed_at',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'raw_response' => 'array',
        'completed_at' => 'datetime',
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
}
