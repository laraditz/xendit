<?php

namespace Laraditz\Xendit\Models;

use Illuminate\Database\Eloquent\Model;

class XenditChannel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'country',
        'currency',
    ];
}
