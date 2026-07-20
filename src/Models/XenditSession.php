<?php

namespace Laraditz\Xendit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laraditz\Xendit\Enums\SessionMode;
use Laraditz\Xendit\Enums\SessionStatus;
use Laraditz\Xendit\Enums\SessionType;
use Laraditz\Xendit\Models\XenditTransaction;

class XenditSession extends Model
{
    use SoftDeletes;

    protected $table = 'xendit_sessions';

    protected $fillable = [
        'reference_id', 'payment_session_id', 'payable_id', 'payable_type',
        'session_type', 'mode', 'status',
        'amount', 'currency', 'country', 'description',
        'customer_id', 'customer',
        'payment_link_url', 'components_sdk_key',
        'success_return_url', 'cancel_return_url',
        'payment_id', 'payment_token_id',
        'metadata', 'session_details',
        'for_user_id', 'split_rule_id',
        'expires_at', 'completed_at', 'canceled_at',
    ];

    protected $casts = [
        'status'          => SessionStatus::class,
        'session_type'    => SessionType::class,
        'mode'            => SessionMode::class,
        'amount'          => 'decimal:2',
        'customer'        => 'array',
        'metadata'        => 'array',
        'session_details' => 'array',
        'expires_at'      => 'datetime',
        'completed_at'    => 'datetime',
        'canceled_at'     => 'datetime',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function xenditCustomer(): BelongsTo
    {
        return $this->belongsTo(XenditCustomer::class, 'customer_id', 'xendit_id');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(XenditTransaction::class, 'source');
    }

    public function markAsCompleted(): self
    {
        $this->update(['status' => SessionStatus::Completed, 'completed_at' => now()]);
        return $this;
    }

    public function markAsExpired(): self
    {
        $this->update(['status' => SessionStatus::Expired]);
        return $this;
    }

    public function markAsCanceled(): self
    {
        $this->update(['status' => SessionStatus::Canceled, 'canceled_at' => now()]);
        return $this;
    }

    public function scopeActive($query)
    {
        return $query->where('status', SessionStatus::Active->value);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', SessionStatus::Completed->value);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', SessionStatus::Expired->value);
    }

    public function scopeReferenceId($query, string $referenceId)
    {
        return $query->where('reference_id', $referenceId);
    }

    public function scopeMatchingReferenceId($query, string $referenceId)
    {
        return $query->where('reference_id', Str::before($referenceId, '_'));
    }

    public function scopePaymentSessionId($query, string $id)
    {
        return $query->where('payment_session_id', $id);
    }

    public function scopeForUserId($query, string $userId)
    {
        return $query->where('for_user_id', $userId);
    }

    public function scopeSplitRuleId($query, string $ruleId)
    {
        return $query->where('split_rule_id', $ruleId);
    }
}
