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
- 🏷️ Custom HTTP headers on any API call (`for-user-id`, `with-split-rule`, etc.)

## API Coverage

- ✅ **[Payment Request](docs/payment-request.md)** - Create, get, cancel, simulate
- ✅ **[Payment](docs/payment.md)** - Get status, cancel, capture
- ✅ **[Payment Token](docs/payment-token.md)** - Create, get, deactivate
- ✅ **[Customer](docs/customer.md)** - Create, get, list, update
- ✅ **[Session](docs/session.md)** - Create, get, cancel with DB persistence
- ✅ **[Refund](docs/refund.md)** - Create refunds
- ✅ **[Payment Link](docs/payment-link.md)** - Create and manage payment links
- ✅ **[Transaction](docs/transaction.md)** - Get and list transactions
- ✅ **[Webhooks](docs/webhooks.md)** - All webhook events supported
- ✅ **[Logging](docs/logging.md)** - Outbound API call audit trail with retention/pruning

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

Run the seeder:

```bash
php artisan db:seed --class=Laraditz\\Xendit\\Database\\Seeders\\DatabaseSeeder
```

Add your Xendit credentials to `.env`:

```env
XENDIT_API_KEY=your-secret-api-key
XENDIT_WEBHOOK_SECRET=your-webhook-verification-token
XENDIT_CURRENCY=MYR
```

## Usage

### Creating Payment Request (Fluent API)

```php
use Laraditz\Xendit\Facades\Xendit;

$payment = Xendit::paymentRequest()
    ->amount(100000)
    ->currency('MYR')
    ->description('Payment for Order #123')
    ->ewallets('SHOPEEPAY') // Specify channel code
    ->successUrl('https://yourapp.com/success')
    ->failureUrl('https://yourapp.com/failed')
    ->metadata(['order_id' => 123])
    ->create();

// Redirect user to payment page
return redirect($payment->payment_url);
```

### Payment Request with Array

```php
use Laraditz\Xendit\Facades\Xendit;

// Using array parameter
$payment = Xendit::paymentRequest()->create([
    'reference_id' => 'ORDER-123',
    'amount' => 100000,
    'currency' => 'MYR',
    'channel_code' => 'SHOPEEPAY',
    'channel_properties' => [
        'success_return_url' => 'https://yourapp.com/success',
        'failure_return_url' => 'https://yourapp.com/failed',
    ],
    'description' => 'Payment for Order #123',
    'metadata' => [
        'order_id' => 123,
    ],
]);
```

### Payment Request with Specific Channels

```php
use Laraditz\Xendit\Facades\Xendit;

// E-wallet (ShopeePay)
$payment = Xendit::paymentRequest()
    ->amount(50000)
    ->ewallets('SHOPEEPAY')
    ->successUrl('https://yourapp.com/success')
    ->create();

// Virtual Account (BCA)
$payment = Xendit::paymentRequest()
    ->amount(50000)
    ->virtualAccounts('BCA')
    ->create();

// QR Code (QRIS)
$payment = Xendit::paymentRequest()
    ->amount(75000)
    ->qrCode('QRIS')
    ->create();

// Cards
$payment = Xendit::paymentRequest()
    ->amount(100000)
    ->card()
    ->create();

// Or use specific channel code directly
$payment = Xendit::paymentRequest()
    ->amount(100000)
    ->channelCode('GRABPAY')
    ->create();
```

### Payment Request with Channel Properties

```php
use Laraditz\Xendit\Facades\Xendit;

// Using channel properties for additional configuration
$payment = Xendit::paymentRequest()
    ->amount(250000)
    ->currency('MYR')
    ->channelCode('SHOPEEPAY')
    ->channelProperties('SHOPEEPAY', [
        'success_return_url' => 'https://yourapp.com/success',
        'failure_return_url' => 'https://yourapp.com/failed',
    ])
    ->metadata([
        'order_id' => 123,
    ])
    ->create();
```

### Attaching Payments to Models (Polymorphic)

```php
// Attach payment to Order model
$payment = Xendit::paymentRequest()
    ->amount(100000)
    ->currency('MYR')
    ->ewallets('SHOPEEPAY')
    ->for($order)
    ->create();

// Attach payment to User model
$payment = Xendit::paymentRequest()
    ->amount(50000)
    ->card()
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

### Creating Customers

```php
use Laraditz\Xendit\Facades\Xendit;

// Create an individual customer
$customer = Xendit::customer()
    ->referenceId('user-001')
    ->givenNames('John')
    ->surname('Doe')
    ->email('john@example.com')
    ->mobileNumber('+60123456789')
    ->create();

// $customer is a persisted XenditCustomer model
echo $customer->xendit_id; // Xendit's customer ID

// Get a customer by Xendit ID
$data = Xendit::customer()->get('cust_abc123');

// List customers by reference ID
$list = Xendit::customer()->list('user-001');

// Update a customer
$updated = Xendit::customer()->update('cust_abc123', [
    'email' => 'new@example.com',
]);
```

### Creating Sessions

```php
use Laraditz\Xendit\Facades\Xendit;

// Create a PAY session (Payment Link mode)
$session = Xendit::session()
    ->referenceId('order-001')
    ->amount(100.00)
    ->currency('MYR')
    ->country('MY')
    ->sessionType('PAY')
    ->mode('PAYMENT_LINK')
    ->successUrl('https://yourapp.com/success')
    ->cancelUrl('https://yourapp.com/cancel')
    ->create();

// $session is a persisted XenditSession model
return redirect($session->payment_link_url);

// Get session details from Xendit API
$data = Xendit::session()->get($session->payment_session_id);

// Cancel a session
Xendit::session()->cancel($session->payment_session_id);
```

### Processing Refunds

```php
use Laraditz\Xendit\Enums\RefundReason;
use Laraditz\Xendit\Facades\Xendit;

// Create a refund — persists a local XenditRefund record immediately
$refund = Xendit::refund()->create([
    'payment_request_id' => $paymentRequestId,
    'amount' => 50000,
    'reason' => RefundReason::RequestedByCustomer->value,
]);

$refund->refund_id; // Xendit's refund ID
$refund->status;    // RefundStatus::Pending
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

### Per-Service API Versions

By default no `api-version` header is sent. You can configure per-service defaults in `config/xendit.php`, override them per call, or suppress them entirely:

```php
// config/xendit.php — configure per-service defaults (all optional)
'api_versions' => [
    'payment_request' => '2024-11-11',
    'session'         => '2024-05-01',
    // any service key set to null suppresses the header even if the service has a default
    // 'payment' => null,
],
```

```php
// Per-call override — takes precedence over config
Xendit::session()
    ->withApiVersion('2024-05-01')
    ->amount(100.00)
    ->sessionType('PAY')
    ->mode('PAYMENT_LINK')
    ->create();

// Suppress for this call only
Xendit::session()
    ->withoutApiVersion()
    ->get('ps-abc123');
```

Available config keys: `payment_request`, `payment`, `payment_token`, `session`, `customer`, `refund`, `payment_link`, `transaction`.

### Custom HTTP Headers (Sub-accounts & Split Rules)

Any builder supports arbitrary request headers via `withHeader()` / `withHeaders()`:

```php
// Single header
Xendit::session()->withHeader('idempotency-key', 'abc')->create();

// Multiple headers at once
Xendit::session()->withHeaders(['idempotency-key' => 'abc'])->create();

// Works on get() and cancel() too
Xendit::session()->withHeader('for-user-id', 'sub-account-id')->get('ps-abc123');
```

`SessionBuilder` and `PaymentRequestBuilder` also expose named shortcuts that set the header **and** persist the value to the database for filtering and auditing:

```php
// Session — for-user-id header + xendit_sessions.for_user_id column
$session = Xendit::session()
    ->forUserId('sub-account-user-id')   // sets 'for-user-id' header
    ->withSplitRule('split-rule-id')      // sets 'with-split-rule' header
    ->amount(100.00)
    ->sessionType('PAY')
    ->mode('PAYMENT_LINK')
    ->create();

// Payment Request — same pattern → xendit_payments.for_user_id / split_rule_id
$payment = Xendit::paymentRequest()
    ->forUserId('sub-account-user-id')
    ->withSplitRule('split-rule-id')
    ->amount(100.00)
    ->ewallets('SHOPEEPAY')
    ->create();

// Query by sub-account or split rule
XenditSession::forUserId('sub-account-user-id')->get();
XenditPayment::forUserId('sub-account-user-id')->get();
XenditSession::splitRuleId('split-rule-id')->get();
XenditPayment::splitRuleId('split-rule-id')->get();
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
use Laraditz\Xendit\Events\RefundFailed;
use Laraditz\Xendit\Events\SessionCreated;
use Laraditz\Xendit\Events\SessionCompleted;
use Laraditz\Xendit\Events\SessionExpired;
use Laraditz\Xendit\Events\SessionCanceled;

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
        LogRefundCreated::class,
    ],
    RefundSucceeded::class => [
        ProcessRefund::class,
    ],
    RefundFailed::class => [
        NotifyRefundFailure::class,
    ],

    // Session events
    SessionCreated::class => [
        LogSessionCreated::class,
    ],
    SessionCompleted::class => [
        FulfillOrder::class,
    ],
    SessionExpired::class => [
        CancelOrder::class,
    ],
    SessionCanceled::class => [
        ReleaseReservedStock::class,
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
        // $event->payload is the full webhook envelope; refund fields live under 'data'
        $refundData = $event->payload['data'];

        // Process refund
        $order = Order::where('payment_id', $refundData['payment_request_id'])->first();
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

## Documentation

For detailed documentation on each service, please refer to:

- **[Payment Request](docs/payment-request.md)** - Complete guide to creating payment requests with fluent builder
- **[Payment](docs/payment.md)** - Managing payment status, cancellation, and capture
- **[Payment Token](docs/payment-token.md)** - Saving and managing customer payment methods
- **[Customer](docs/customer.md)** - Creating and managing Xendit customers with DB persistence
- **[Session](docs/session.md)** - Creating secure payment sessions for checkout flows
- **[Refund](docs/refund.md)** - Processing full and partial refunds
- **[Payment Link](docs/payment-link.md)** - Generating shareable payment links for invoices
- **[Transaction](docs/transaction.md)** - Querying and listing all transactions
- **[Webhooks](docs/webhooks.md)** - Handling webhook events and notifications
- **[Logging](docs/logging.md)** - Outbound API call audit trail with retention/pruning

## Testing

```bash
composer test
```

## Changelog

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
