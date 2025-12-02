<?php

namespace Laraditz\Xendit\Enums;

enum PaymentType: string
{
    case Invoice = 'invoice';
    case Ewallet = 'ewallet';
    case VirtualAccount = 'virtual_account';
    case QrCode = 'qr_code';
    case PaymentRequest = 'payment_request';
    case RetailOutlet = 'retail_outlet';
    case Card = 'card';

    public function label(): string
    {
        return match($this) {
            self::Invoice => 'Invoice',
            self::Ewallet => 'E-Wallet',
            self::VirtualAccount => 'Virtual Account',
            self::QrCode => 'QR Code',
            self::PaymentRequest => 'Payment Request',
            self::RetailOutlet => 'Retail Outlet',
            self::Card => 'Card',
        };
    }
}
