<?php

namespace Laraditz\Xendit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laraditz\Xendit\Enums\CustomerType;

class XenditCustomer extends Model
{
    use SoftDeletes;

    protected $table = 'xendit_customers';

    protected $fillable = [
        'reference_id', 'xendit_id', 'type',
        'email', 'mobile_number', 'phone_number',
        'individual_detail', 'business_detail', 'addresses',
        'kyc_documents', 'identity_accounts',
        'metadata', 'customer_details',
    ];

    protected $casts = [
        'type'              => CustomerType::class,
        'individual_detail' => 'array',
        'business_detail'   => 'array',
        'addresses'         => 'array',
        'kyc_documents'     => 'array',
        'identity_accounts' => 'array',
        'metadata'          => 'array',
        'customer_details'  => 'array',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(XenditSession::class, 'customer_id', 'xendit_id');
    }

    public function scopeReferenceId($query, string $referenceId)
    {
        return $query->where('reference_id', $referenceId);
    }

    public function scopeXenditId($query, string $xenditId)
    {
        return $query->where('xendit_id', $xenditId);
    }
}
