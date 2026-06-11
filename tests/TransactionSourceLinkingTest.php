<?php

namespace Laraditz\Xendit\Tests;

use Illuminate\Support\Facades\Http;
use Laraditz\Xendit\Enums\TransactionStatus;
use Laraditz\Xendit\Enums\TransactionType;
use Laraditz\Xendit\Models\XenditPayment;
use Laraditz\Xendit\Models\XenditSession;
use Laraditz\Xendit\Models\XenditTransaction;
use Laraditz\Xendit\Services\PaymentRequestService;
use Laraditz\Xendit\Services\SessionService;

class TransactionSourceLinkingTest extends TestCase
{
    public function test_unlinked_transaction_is_linked_when_matching_payment_is_fetched(): void
    {
        $payment = XenditPayment::create([
            'external_id' => 'ORDER-LINK-PAYMENT',
            'payment_type' => 'PAYMENT_REQUEST',
            'amount' => 1520,
        ]);

        $transaction = XenditTransaction::create([
            'transaction_id' => 'txn_unlinked_payment',
            'reference_id' => 'ORDER-LINK-PAYMENT',
            'type' => TransactionType::Payment,
            'status' => TransactionStatus::Success,
            'amount' => 1520,
            'net_amount' => 1518.8,
        ]);

        Http::fake([
            'api.xendit.co/v3/payment_requests/pr_1' => Http::response([
                'id' => 'pr_1',
                'reference_id' => 'ORDER-LINK-PAYMENT',
            ], 200),
        ]);

        app(PaymentRequestService::class)->get('pr_1');

        $transaction->refresh();

        $this->assertSame($payment->id, $transaction->source_id);
        $this->assertSame(XenditPayment::class, $transaction->source_type);
    }

    public function test_unlinked_transaction_is_linked_when_matching_session_is_fetched(): void
    {
        $session = XenditSession::create([
            'reference_id' => 'ORDER-LINK-SESSION',
            'session_type' => 'PAY',
        ]);

        $transaction = XenditTransaction::create([
            'transaction_id' => 'txn_unlinked_session',
            'reference_id' => 'ORDER-LINK-SESSION',
            'type' => TransactionType::Payment,
            'status' => TransactionStatus::Success,
            'amount' => 1520,
            'net_amount' => 1518.8,
        ]);

        Http::fake([
            'api.xendit.co/sessions/sess_1' => Http::response([
                'id' => 'sess_1',
                'reference_id' => 'ORDER-LINK-SESSION',
            ], 200),
        ]);

        app(SessionService::class)->get('sess_1');

        $transaction->refresh();

        $this->assertSame($session->id, $transaction->source_id);
        $this->assertSame(XenditSession::class, $transaction->source_type);
    }

    public function test_already_linked_transaction_is_untouched(): void
    {
        $payment = XenditPayment::create([
            'external_id' => 'ORDER-ALREADY-LINKED',
            'payment_type' => 'PAYMENT_REQUEST',
            'amount' => 1520,
        ]);

        $otherSession = XenditSession::create([
            'reference_id' => 'ORDER-ALREADY-LINKED-OTHER',
            'session_type' => 'PAY',
        ]);

        $transaction = XenditTransaction::create([
            'transaction_id' => 'txn_already_linked',
            'reference_id' => 'ORDER-ALREADY-LINKED',
            'type' => TransactionType::Payment,
            'status' => TransactionStatus::Success,
            'amount' => 1520,
            'net_amount' => 1518.8,
            'source_id' => $otherSession->id,
            'source_type' => XenditSession::class,
        ]);

        Http::fake([
            'api.xendit.co/v3/payment_requests/pr_1' => Http::response([
                'id' => 'pr_1',
                'reference_id' => 'ORDER-ALREADY-LINKED',
            ], 200),
        ]);

        app(PaymentRequestService::class)->get('pr_1');

        $transaction->refresh();

        $this->assertSame($otherSession->id, $transaction->source_id);
        $this->assertSame(XenditSession::class, $transaction->source_type);
    }

    public function test_no_op_when_no_local_source_matches(): void
    {
        $transaction = XenditTransaction::create([
            'transaction_id' => 'txn_no_match',
            'reference_id' => 'ORDER-NO-MATCH',
            'type' => TransactionType::Payment,
            'status' => TransactionStatus::Success,
            'amount' => 1520,
            'net_amount' => 1518.8,
        ]);

        Http::fake([
            'api.xendit.co/v3/payment_requests/pr_1' => Http::response([
                'id' => 'pr_1',
                'reference_id' => 'ORDER-NO-MATCH',
            ], 200),
        ]);

        app(PaymentRequestService::class)->get('pr_1');

        $transaction->refresh();

        $this->assertNull($transaction->source_id);
        $this->assertNull($transaction->source_type);
    }
}
