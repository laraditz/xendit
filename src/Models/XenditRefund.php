<?php

namespace Laraditz\Xendit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laraditz\Xendit\Enums\RefundFailureCode;
use Laraditz\Xendit\Enums\RefundReason;
use Laraditz\Xendit\Enums\RefundStatus;

class XenditRefund extends Model
{
    use SoftDeletes;

    protected $table = 'xendit_refunds';

    protected $fillable = [
        'payment_id',
        'refund_id',
        'payment_request_id',
        'reference_id',
        'currency',
        'amount',
        'status',
        'reason',
        'failure_code',
        'refund_fee_amount',
        'metadata',
        'raw_response',
    ];

    protected $casts = [
        'status' => RefundStatus::class,
        'reason' => RefundReason::class,
        'failure_code' => RefundFailureCode::class,
        'amount' => 'decimal:2',
        'refund_fee_amount' => 'decimal:2',
        'metadata' => 'array',
        'raw_response' => 'array',
    ];

    /**
     * The XenditPayment this refund belongs to
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(XenditPayment::class, 'payment_id');
    }
}
