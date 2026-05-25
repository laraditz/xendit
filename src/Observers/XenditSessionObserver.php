<?php

namespace Laraditz\Xendit\Observers;

use Laraditz\Xendit\Enums\SessionStatus;
use Laraditz\Xendit\Models\XenditSession;

class XenditSessionObserver
{
    public function creating(XenditSession $session): void
    {
        if (is_null($session->status)) {
            $session->status = SessionStatus::Active;
        }

        if (is_null($session->currency)) {
            $session->currency = config('xendit.default_currency', 'MYR');
        }
    }
}
