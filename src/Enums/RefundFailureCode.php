<?php

namespace Laraditz\Xendit\Enums;

enum RefundFailureCode: string
{
    case AccountAccessBlocked = 'ACCOUNT_ACCESS_BLOCKED';
    case AccountNotFound = 'ACCOUNT_NOT_FOUND';
    case DuplicateError = 'DUPLICATE_ERROR';
    case InsufficientBalance = 'INSUFFICIENT_BALANCE';
    case RefundFailed = 'REFUND_FAILED';
}
