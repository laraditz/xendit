<?php

namespace Laraditz\Xendit\Database\Seeders;

use Illuminate\Database\Seeder;
use Laraditz\Xendit\Models\XenditChannel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class XenditChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channels = [
            [
                'code' => 'AFFIN_FPX',
                'name' => 'Affin Bank FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'AFFIN_FPX_BUSINESS',
                'name' => 'Affin Bank FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'AGRO_FPX',
                'name' => 'Agrobank FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'AGRO_FPX_BUSINESS',
                'name' => 'Agrobank FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'ALIPAY',
                'name' => 'Alipay',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'EWALLET'
            ],
            [
                'code' => 'ALLIANCE_FPX',
                'name' => 'Alliance Bank FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'ALLIANCE_FPX_BUSINESS',
                'name' => 'Alliance Bank FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'AMBANK_FPX',
                'name' => 'AmBank FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'AMBANK_FPX_BUSINESS',
                'name' => 'AmBank FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'AMBANK_VIRTUAL_ACCOUNT',
                'name' => 'AmBank Virtual Account',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'BANK_TRANSFER'
            ],
            [
                'code' => 'BNP_FPX_BUSINESS',
                'name' => 'BNP FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'BOC_FPX',
                'name' => 'Bank of China FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'BSN_FPX',
                'name' => 'BSN FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'CARDS',
                'name' => 'Cards',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'CARDS'
            ],
            [
                'code' => 'CIMB_FPX',
                'name' => 'CIMB FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'CIMB_FPX_BUSINESS',
                'name' => 'CIMB FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'CITIBANK_FPX_BUSINESS',
                'name' => 'Citibank FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'DEUTSCHE_FPX_BUSINESS',
                'name' => 'Deutsche Bank FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'GRABPAY',
                'name' => 'GrabPay',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'EWALLET'
            ],
            [
                'code' => 'HLB_FPX',
                'name' => 'Hong Leong Bank FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'HLB_FPX_BUSINESS',
                'name' => 'Hong Leong Bank FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'HSBC_FPX',
                'name' => 'HSBC FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'HSBC_FPX_BUSINESS',
                'name' => 'HSBC FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'ISLAM_FPX',
                'name' => 'Bank Islam FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'ISLAM_FPX_BUSINESS',
                'name' => 'Bank Islam FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'KFH_FPX',
                'name' => 'Kuwait Finance House FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'KFH_FPX_BUSINESS',
                'name' => 'Kuwait Finance House FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'MAYB2E_FPX',
                'name' => 'Maybank2E FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'MAYB2E_FPX_BUSINESS',
                'name' => 'Maybank2E FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'MAYB2U_FPX',
                'name' => 'Maybank2U FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'MUAMALAT_FPX',
                'name' => 'Bank Muamalat FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'MUAMALAT_FPX_BUSINESS',
                'name' => 'Bank Muamalat FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'OCBC_FPX',
                'name' => 'OCBC Bank FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'OCBC_FPX_BUSINESS',
                'name' => 'OCBC Bank FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'PUBLIC_FPX',
                'name' => 'Public Bank FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'PUBLIC_FPX_BUSINESS',
                'name' => 'Public Bank FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'RAKYAT_FPX',
                'name' => 'Bank Rakyat FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'RAKYAT_FPX_BUSINESS',
                'name' => 'Bank Rakyat FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'RHB_FPX',
                'name' => 'RHB Bank FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'RHB_FPX_BUSINESS',
                'name' => 'RHB Bank FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'SCH_FPX',
                'name' => 'Standard Chartered FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'SCH_FPX_BUSINESS',
                'name' => 'Standard Chartered FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'SHOPEEPAY',
                'name' => 'ShopeePay',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'EWALLET'
            ],
            [
                'code' => 'TOUCHNGO',
                'name' => 'Touch ‘n Go',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'EWALLET'
            ],
            [
                'code' => 'UOB_FPX',
                'name' => 'UOB FPX',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'UOB_FPX_BUSINESS',
                'name' => 'UOB FPX (Business)',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'ONLINE_BANKING'
            ],
            [
                'code' => 'WECHATPAY',
                'name' => 'WeChat Pay',
                'country' => 'MY',
                'currency' => 'MYR',
                'type' => 'EWALLET'
            ]
        ];

        foreach ($channels as $channel) {
            $code = $channel['code'];
            unset($channel['code']);

            XenditChannel::updateOrCreate(
                ['code' => $code],
                $channel
            );
        }
    }
}
