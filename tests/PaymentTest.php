<?php

namespace Laraditz\Xendit\Tests;

use Laraditz\Xendit\Models\XenditPayment;

class PaymentTest extends TestCase
{
    public function test_xendit_payment_has_for_user_id_and_split_rule_id_columns(): void
    {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('xendit_payments');
        $this->assertContains('for_user_id', $columns);
        $this->assertContains('split_rule_id', $columns);
    }

    public function test_xendit_payment_scope_for_user_id(): void
    {
        XenditPayment::create([
            'external_id'  => 'pmnt-uid-1',
            'payment_type' => 'PAYMENT_REQUEST',
            'amount'       => 100,
            'for_user_id'  => 'user-abc',
        ]);

        XenditPayment::create([
            'external_id'  => 'pmnt-uid-2',
            'payment_type' => 'PAYMENT_REQUEST',
            'amount'       => 100,
            'for_user_id'  => 'user-xyz',
        ]);

        $results = XenditPayment::forUserId('user-abc')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('pmnt-uid-1', $results->first()->external_id);
    }

    public function test_xendit_payment_scope_split_rule_id(): void
    {
        XenditPayment::create([
            'external_id'   => 'pmnt-rule-1',
            'payment_type'  => 'PAYMENT_REQUEST',
            'amount'        => 100,
            'split_rule_id' => 'rule-aaa',
        ]);

        XenditPayment::create([
            'external_id'   => 'pmnt-rule-2',
            'payment_type'  => 'PAYMENT_REQUEST',
            'amount'        => 100,
            'split_rule_id' => 'rule-bbb',
        ]);

        $results = XenditPayment::splitRuleId('rule-aaa')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('pmnt-rule-1', $results->first()->external_id);
    }
}
