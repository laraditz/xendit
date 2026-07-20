<?php

namespace Laraditz\Xendit\Tests\Enums;

use Laraditz\Xendit\Enums\TransactionType;
use Laraditz\Xendit\Tests\TestCase;

class TransactionTypeTest extends TestCase
{
    public function test_payment_and_refund_values_match_xendit_api(): void
    {
        $this->assertSame('PAYMENT', TransactionType::Payment->value);
        $this->assertSame('REFUND', TransactionType::Refund->value);
        $this->assertSame('DISBURSEMENT', TransactionType::Disbursement->value);
        $this->assertSame('ADJUSTMENT_ADD', TransactionType::AdjustmentAdd->value);
        $this->assertSame('ADJUSTMENT_DEDUCT', TransactionType::AdjustmentDeduct->value);
    }

    public function test_all_25_xendit_types_are_covered(): void
    {
        $expected = [
            'ADJUSTMENT_ADD', 'ADJUSTMENT_DEDUCT', 'BNPL_PARTNER_SETTLEMENT_CREDIT',
            'BNPL_PARTNER_SETTLEMENT_DEBIT', 'CASHBACK_FEE', 'CASHBACK_VAT', 'CHARGEBACK',
            'CONVERSION', 'DISBURSEMENT', 'FOREX_DEDUCTION', 'FOREX_DEPOSIT',
            'IN_PERSON_PAYMENT', 'LOAN_REPAYMENT', 'OTHER', 'PAYMENT', 'REFUND',
            'REMITTANCE', 'REMITTANCE_COLLECTION_PAYMENT', 'REMITTANCE_PAYOUT',
            'RESERVES_HOLD', 'RESERVES_RELEASE', 'TOPUP', 'TRANSFER_IN', 'TRANSFER_OUT',
            'WITHDRAWAL',
        ];

        $actual = array_map(fn($case) => $case->value, TransactionType::cases());

        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
    }

    public function test_label_humanizes_the_value(): void
    {
        $this->assertSame('Payment', TransactionType::Payment->label());
        $this->assertSame('Adjustment Add', TransactionType::AdjustmentAdd->label());
        $this->assertSame('In Person Payment', TransactionType::InPersonPayment->label());
    }
}
