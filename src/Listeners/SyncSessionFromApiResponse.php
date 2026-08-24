<?php

namespace Laraditz\Xendit\Listeners;

use Laraditz\Xendit\Events\XenditApiResponseReceived;
use Laraditz\Xendit\Models\XenditSession;

class SyncSessionFromApiResponse
{
    public function handle(XenditApiResponseReceived $event): void
    {
        if ($event->method !== 'GET') {
            return;
        }

        if (!preg_match('#^/sessions(/[^/]+)?$#', $event->endpoint)) {
            return;
        }

        XenditSession::syncFromApiResponse($event->response);
    }
}
