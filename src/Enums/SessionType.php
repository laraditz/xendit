<?php

namespace Laraditz\Xendit\Enums;

enum SessionType: string
{
    case Pay          = 'PAY';
    case Save         = 'SAVE';
    case Subscription = 'SUBSCRIPTION';
}
