<?php

namespace Laraditz\Xendit\Listeners;

use Laraditz\Xendit\Events\XenditApiResponseReceived;
use Laraditz\Xendit\Models\XenditTransaction;

class SyncTransactionFromApiResponse
{
    public function handle(XenditApiResponseReceived $event): void
    {
        if ($event->method !== 'GET') {
            return;
        }

        if (!preg_match('#^/transactions(/[^/]+)?$#', $event->endpoint)) {
            return;
        }

        $transactions = isset($event->response['data']) && is_array($event->response['data'])
            ? $event->response['data']
            : [$event->response];

        foreach ($transactions as $transaction) {
            XenditTransaction::syncFromApiResponse($transaction);
        }
    }
}
