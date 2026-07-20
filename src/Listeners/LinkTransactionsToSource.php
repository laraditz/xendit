<?php

namespace Laraditz\Xendit\Listeners;

use Laraditz\Xendit\Events\XenditApiResponseReceived;
use Laraditz\Xendit\Models\XenditPayment;
use Laraditz\Xendit\Models\XenditSession;
use Laraditz\Xendit\Models\XenditTransaction;

class LinkTransactionsToSource
{
    public function handle(XenditApiResponseReceived $event): void
    {
        if ($event->method !== 'GET') {
            return;
        }

        $referenceId = data_get($event->response, 'reference_id');

        if (!$referenceId) {
            return;
        }

        $source = match (true) {
            (bool) preg_match('#^/v3/payment_requests/[^/]+$#', $event->endpoint) =>
                XenditPayment::where('external_id', $referenceId)->first(),
            (bool) preg_match('#^/sessions/[^/]+$#', $event->endpoint) =>
                XenditSession::matchingReferenceId($referenceId)->first(),
            default => null,
        };

        if (!$source) {
            return;
        }

        XenditTransaction::unlinked()
            ->where('reference_id', $referenceId)
            ->get()
            ->each(fn (XenditTransaction $transaction) => $transaction->linkSource($source));
    }
}
