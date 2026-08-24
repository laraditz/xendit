<?php

namespace Laraditz\Xendit\Enums;

enum RefundReason: string
{
    case Fraudulent = 'FRAUDULENT';
    case Duplicate = 'DUPLICATE';
    case RequestedByCustomer = 'REQUESTED_BY_CUSTOMER';
    case Cancellation = 'CANCELLATION';
    case Others = 'OTHERS';
}
