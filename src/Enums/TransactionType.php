<?php

namespace Laraditz\Xendit\Enums;

use Illuminate\Support\Str;

enum TransactionType: string
{
    case AdjustmentAdd = 'ADJUSTMENT_ADD';
    case AdjustmentDeduct = 'ADJUSTMENT_DEDUCT';
    case BnplPartnerSettlementCredit = 'BNPL_PARTNER_SETTLEMENT_CREDIT';
    case BnplPartnerSettlementDebit = 'BNPL_PARTNER_SETTLEMENT_DEBIT';
    case CashbackFee = 'CASHBACK_FEE';
    case CashbackVat = 'CASHBACK_VAT';
    case Chargeback = 'CHARGEBACK';
    case Conversion = 'CONVERSION';
    case Disbursement = 'DISBURSEMENT';
    case ForexDeduction = 'FOREX_DEDUCTION';
    case ForexDeposit = 'FOREX_DEPOSIT';
    case InPersonPayment = 'IN_PERSON_PAYMENT';
    case LoanRepayment = 'LOAN_REPAYMENT';
    case Other = 'OTHER';
    case Payment = 'PAYMENT';
    case Refund = 'REFUND';
    case Remittance = 'REMITTANCE';
    case RemittanceCollectionPayment = 'REMITTANCE_COLLECTION_PAYMENT';
    case RemittancePayout = 'REMITTANCE_PAYOUT';
    case ReservesHold = 'RESERVES_HOLD';
    case ReservesRelease = 'RESERVES_RELEASE';
    case Topup = 'TOPUP';
    case TransferIn = 'TRANSFER_IN';
    case TransferOut = 'TRANSFER_OUT';
    case Withdrawal = 'WITHDRAWAL';

    public function label(): string
    {
        return Str::of($this->value)->lower()->replace('_', ' ')->title()->toString();
    }
}
