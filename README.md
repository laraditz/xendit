# Laravel Xendit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laraditz/xendit.svg?style=flat-square)](https://packagist.org/packages/laraditz/xendit)
[![Total Downloads](https://img.shields.io/packagist/dt/laraditz/xendit.svg?style=flat-square)](https://packagist.org/packages/laraditz/xendit)
![GitHub Actions](https://github.com/laraditz/xendit/actions/workflows/main.yml/badge.svg)

A Laravel package for seamless integration with Xendit payment gateway. Built from scratch using Laravel's HTTP client, this package provides a fluent API for creating payments, managing transactions, and handling webhooks - all with database persistence and event-driven architecture.

## Features

- 🔥 Fluent API for creating payments
- 💳 Support for all payment methods (E-Wallet, Virtual Account, QR Code, Cards, OTC)
- 🗄️ Database persistence for payments, transactions, and webhooks
- 🔔 Automatic webhook handling with signature verification
- 🎯 Event-driven architecture (Payment, Refund, Token events)
- 🔗 Polymorphic relationships (attach payments to any model)
- 📦 Uses Laravel's HTTP client (no Guzzle dependency)
- 🛡️ Type-safe with PHP 8.2+ Backed Enums
- 💰 Complete refund management
- 🔑 Payment token (saved payment methods) support
- 🔗 Payment links generation
- 📊 Transaction querying and listing
- 🎫 Session management

## API Coverage

- ✅ **Payment Request** - Create, get, cancel, simulate
- ✅ **Payment** - Get status, cancel, capture
- ✅ **Payment Token** - Create, get, deactivate
- ✅ **Session** - Create, get, cancel
- ✅ **Refund** - Create refunds
- ✅ **Payment Link** - Create and manage payment links
- ✅ **Transaction** - Get and list transactions
- ✅ **Webhooks** - All webhook events supported

## Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, or 12.x

## Installation

Install the package via composer:

```bash
composer require laraditz/xendit
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --tag=xendit-config
php artisan vendor:publish --tag=xendit-migrations
```

Run the migrations:

```bash
php artisan migrate
```

Add your Xendit credentials to `.env`:

```env
XENDIT_API_KEY=your-secret-api-key
XENDIT_WEBHOOK_SECRET=your-webhook-verification-token
XENDIT_CURRENCY=MYR
```

## Usage

### Creating Payment Request (All Payment Methods)

```php
use Laraditz\Xendit\Facades\Xendit;

$payment = Xendit::paymentRequest()
    ->amount(100000)
    ->description('Payment for Order #123')
    ->email('customer@example.com')
    ->phone('+60123456789')
    ->allMethods() // Enable all payment methods (e-wallets, VA, QR, OTC)
    ->successUrl('https://yourapp.com/success')
    ->failureUrl('https://yourapp.com/failed')
    ->metadata(['order_id' => 123])
    ->create();

// Redirect user to payment page
return redirect($payment->payment_url);
```

### Payment Request with Specific Methods

```php
use Laraditz\Xendit\Facades\Xendit;

// Only e-wallets and virtual accounts
$payment = Xendit::paymentRequest()
    ->amount(50000)
    ->email('customer@example.com')
    ->ewallets() // DANA, OVO, ShopeePay, LinkAja, etc.
    ->virtualAccounts() // BCA, BNI, BRI, Mandiri, etc.
    ->create();

// Only QR code
$payment = Xendit::paymentRequest()
    ->amount(75000)
    ->email('customer@example.com')
    ->qrCode() // QRIS
    ->create();
```

### Payment Request with Line Items

```php
use Laraditz\Xendit\Facades\Xendit;

$payment = Xendit::paymentRequest()
    ->amount(250000)
    ->email('customer@example.com')
    ->items([
        [
            'name' => 'Product 1',
            'quantity' => 2,
            'price' => 100000,
        ],
        [
            'name' => 'Product 2',
            'quantity' => 1,
            'price' => 50000,
        ],
    ])
    ->allMethods()
    ->create();
```

### Attaching Payments to Models (Polymorphic)

```php
// Attach payment to Order model
$payment = Xendit::paymentRequest()
    ->amount(100000)
    ->allMethods()
    ->for($order)
    ->create();

// Attach payment to User model
$payment = Xendit::paymentRequest()
    ->amount(50000)
    ->ewallets()
    ->for($user)
    ->create();

// Access payments from your model
$order->payments; // Get all payments for this order
```

### Managing Payments

```php
use Laraditz\Xendit\Facades\Xendit;

// Get payment status
$status = Xendit::payment()->get($paymentId);

// Cancel a payment
Xendit::payment()->cancel($paymentId);

// Capture a payment (for authorized payments)
Xendit::payment()->capture($paymentId, [
    'capture_amount' => 100000,
]);
```

### Working with Payment Tokens (Saved Payment Methods)

```php
use Laraditz\Xendit\Facades\Xendit;

// Create a payment token
$token = Xendit::paymentToken()->create([
    'customer_id' => 'customer-123',
    'type' => 'CARD',
    // ... other token data
]);

// Get token status
$tokenStatus = Xendit::paymentToken()->get($tokenId);

// Deactivate a token
Xendit::paymentToken()->cancel($tokenId);
```

### Creating Sessions

```php
use Laraditz\Xendit\Facades\Xendit;

// Create a session
$session = Xendit::session()->create([
    'amount' => 100000,
    'currency' => 'MYR',
    'success_return_url' => 'https://yourapp.com/success',
    'failure_return_url' => 'https://yourapp.com/failed',
]);

// Get session status
$sessionStatus = Xendit::session()->get($sessionId);

// Cancel a session
Xendit::session()->cancel($sessionId);
```

### Processing Refunds

```php
use Laraditz\Xendit\Facades\Xendit;

// Create a refund
$refund = Xendit::refund()->create([
    'payment_id' => $paymentId,
    'amount' => 50000,
    'reason' => 'Customer request',
]);
```

### Creating Payment Links

```php
use Laraditz\Xendit\Facades\Xendit;

// Create a payment link
$link = Xendit::paymentLink()->create([
    'amount' => 100000,
    'description' => 'Payment for Product',
    'customer' => [
        'email' => 'customer@example.com',
    ],
]);

// Get payment link
$linkDetails = Xendit::paymentLink()->get($linkId);
```

### Querying Transactions

```php
use Laraditz\Xendit\Facades\Xendit;

// Get transaction by ID
$transaction = Xendit::transaction()->get($transactionId);

// List all transactions
$transactions = Xendit::transaction()->list([
    'limit' => 20,
    'after_id' => 'txn_123',
]);
```

### Querying Payments

```php
use Laraditz\Xendit\Models\XenditPayment;
use Laraditz\Xendit\Enums\PaymentStatus;

// Find payment by external ID
$payment = XenditPayment::externalId('ORDER-123')->first();

// Find paid payments
$paidPayments = XenditPayment::paid()->get();

// Find pending payments
$pendingPayments = XenditPayment::pending()->get();

// Filter by status
$payments = XenditPayment::status(PaymentStatus::Paid)->get();

// Check payment status
if ($payment->isPaid()) {
    // Payment is paid
}
```

### Webhook Handling

Webhooks are automatically handled at `/xendit/webhook`. The package will:

1. Verify webhook signature
2. Log webhook to database
3. Update payment status
4. Dispatch Laravel events

#### Available Webhook Events

Listen to webhook events in your `EventServiceProvider`:

```php
use Laraditz\Xendit\Events\PaymentPaid;
use Laraditz\Xendit\Events\PaymentExpired;
use Laraditz\Xendit\Events\PaymentFailed;
use Laraditz\Xendit\Events\PaymentTokenCreated;
use Laraditz\Xendit\Events\PaymentTokenActivated;
use Laraditz\Xendit\Events\RefundCreated;
use Laraditz\Xendit\Events\RefundSucceeded;

protected $listen = [
    // Payment events
    PaymentPaid::class => [
        SendPaymentConfirmationEmail::class,
        ProcessOrder::class,
    ],
    PaymentExpired::class => [
        CancelOrder::class,
    ],
    PaymentFailed::class => [
        NotifyPaymentFailure::class,
    ],

    // Payment token events
    PaymentTokenCreated::class => [
        LogPaymentTokenCreation::class,
    ],
    PaymentTokenActivated::class => [
        EnableSavedPaymentMethod::class,
    ],

    // Refund events
    RefundCreated::class => [
        LogRefundRequest::class,
    ],
    RefundSucceeded::class => [
        ProcessRefund::class,
    ],
];
```

#### Example Listeners

**Payment Event Listener:**

```php
namespace App\Listeners;

use Laraditz\Xendit\Events\PaymentPaid;

class ProcessOrder
{
    public function handle(PaymentPaid $event)
    {
        $payment = $event->payment;

        // Access related model
        $order = $payment->payable; // Returns Order model

        // Process the order
        $order->markAsPaid();
        $order->process();
    }
}
```

**Refund Event Listener:**

```php
namespace App\Listeners;

use Laraditz\Xendit\Events\RefundSucceeded;

class ProcessRefund
{
    public function handle(RefundSucceeded $event)
    {
        $refundData = $event->payload;

        // Process refund
        $order = Order::where('payment_id', $refundData['payment_id'])->first();
        $order->markAsRefunded();
    }
}
```

**Payment Token Event Listener:**

```php
namespace App\Listeners;

use Laraditz\Xendit\Events\PaymentTokenCreated;

class LogPaymentTokenCreation
{
    public function handle(PaymentTokenCreated $event)
    {
        $tokenData = $event->payload;

        // Store token reference or log
        Log::info('Payment token created', $tokenData);
    }
}
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email raditzfarhan@gmail.com instead of using the issue tracker.

## Credits

- [Raditz Farhan](https://github.com/laraditz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
