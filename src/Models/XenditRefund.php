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

    /**
     * Create or update a local refund record from a Xendit Refund API response.
     */
    public static function syncFromApiResponse(array $data): ?self
    {
        $refundId = data_get($data, 'id');

        if (!$refundId) {
            return null;
        }

        $refund = static::firstOrNew(['refund_id' => $refundId]);

        $paymentRequestId = data_get($data, 'payment_request_id');

        $refund->fill([
            'payment_id' => XenditPayment::where('xendit_id', $paymentRequestId)->value('id'),
            'payment_request_id' => $paymentRequestId,
            'reference_id' => data_get($data, 'reference_id'),
            'currency' => data_get($data, 'currency'),
            'amount' => data_get($data, 'amount'),
            'status' => data_get($data, 'status'),
            'reason' => data_get($data, 'reason'),
            'failure_code' => data_get($data, 'failure_code'),
            'refund_fee_amount' => data_get($data, 'refund_fee_amount'),
            'metadata' => data_get($data, 'metadata'),
            'raw_response' => $data,
        ]);

        $refund->save();

        return $refund;
    }
}
