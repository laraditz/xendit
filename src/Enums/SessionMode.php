<?php

namespace Laraditz\Xendit\Enums;

enum SessionMode: string
{
    case PaymentLink = 'PAYMENT_LINK';
    case Components  = 'COMPONENTS';
}
