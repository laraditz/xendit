<?php

namespace Laraditz\Xendit\Enums;

enum PaymentChannel: string
{
    // E-Wallets
    case Dana = 'DANA';
    case Ovo = 'OVO';
    case Shopeepay = 'SHOPEEPAY';
    case Linkaja = 'LINKAJA';
    case Astrapay = 'ASTRAPAY';
    case Jeniuspay = 'JENIUSPAY';

    // Virtual Accounts
    case Bca = 'BCA';
    case Bni = 'BNI';
    case Bri = 'BRI';
    case Mandiri = 'MANDIRI';
    case Permata = 'PERMATA';
    case Cimb = 'CIMB';
    case SahabatSampoerna = 'SAHABAT_SAMPOERNA';
    case Bsi = 'BSI';
    case Bjb = 'BJB';

    // Retail Outlets
    case Alfamart = 'ALFAMART';
    case Indomaret = 'INDOMARET';

    // QR Codes
    case Qris = 'QRIS';

    public function label(): string
    {
        return match($this) {
            self::Dana => 'DANA',
            self::Ovo => 'OVO',
            self::Shopeepay => 'ShopeePay',
            self::Linkaja => 'LinkAja',
            self::Astrapay => 'AstraPay',
            self::Jeniuspay => 'JeniusPay',
            self::Bca => 'BCA',
            self::Bni => 'BNI',
            self::Bri => 'BRI',
            self::Mandiri => 'Mandiri',
            self::Permata => 'Permata',
            self::Cimb => 'CIMB Niaga',
            self::SahabatSampoerna => 'Sahabat Sampoerna',
            self::Bsi => 'BSI',
            self::Bjb => 'BJB',
            self::Alfamart => 'Alfamart',
            self::Indomaret => 'Indomaret',
            self::Qris => 'QRIS',
        };
    }

    public function type(): PaymentType
    {
        return match($this) {
            self::Dana, self::Ovo, self::Shopeepay, self::Linkaja, self::Astrapay, self::Jeniuspay => PaymentType::Ewallet,
            self::Bca, self::Bni, self::Bri, self::Mandiri, self::Permata, self::Cimb, self::SahabatSampoerna, self::Bsi, self::Bjb => PaymentType::VirtualAccount,
            self::Alfamart, self::Indomaret => PaymentType::RetailOutlet,
            self::Qris => PaymentType::QrCode,
        };
    }

    public static function ewallets(): array
    {
        return [
            self::Dana,
            self::Ovo,
            self::Shopeepay,
            self::Linkaja,
            self::Astrapay,
            self::Jeniuspay,
        ];
    }

    public static function virtualAccounts(): array
    {
        return [
            self::Bca,
            self::Bni,
            self::Bri,
            self::Mandiri,
            self::Permata,
            self::Cimb,
            self::SahabatSampoerna,
            self::Bsi,
            self::Bjb,
        ];
    }
}
