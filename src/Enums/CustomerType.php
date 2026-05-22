<?php

namespace Laraditz\Xendit\Enums;

enum CustomerType: string
{
    case Individual = 'INDIVIDUAL';
    case Business   = 'BUSINESS';

    public function label(): string
    {
        return match($this) {
            self::Individual => 'Individual',
            self::Business   => 'Business',
        };
    }
}
