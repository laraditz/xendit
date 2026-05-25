<?php

namespace Laraditz\Xendit\Events;

use Laraditz\Xendit\Models\XenditSession;

class SessionCompleted
{
    public function __construct(
        public readonly XenditSession $session,
    ) {}
}
