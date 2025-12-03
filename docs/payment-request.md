# Payment Request

The Payment Request API is Xendit's unified payment method that allows you to accept payments from multiple payment channels.

**Official Documentation:** https://docs.xendit.co/apidocs/create-payment-request

## Available Methods

### `create(array $data = []): XenditPayment`

Creates a new payment request. Supports both fluent API and array parameter.

**Official API:** `POST /payment_requests`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `reference_id` | string | Yes | Your unique reference ID |
| `amount` | number | Yes | Payment amount |
| `currency` | string | Yes | Currency code (IDR, PHP, THB, VND, MYR) |
| `channel_code` | string | Yes | Payment channel code (SHOPEEPAY, BCA, QRIS, etc.) |
| `channel_properties` | object | No | Channel-specific properties |
| `description` | string | No | Payment description |
| `metadata` | object | No | Custom metadata |
| `country` | string | No | Country code (auto-detected from currency) |
| `capture_method` | string | No | AUTOMATIC or MANUAL (default: AUTOMATIC) |

**Example (Array):**

```php
use Laraditz\Xendit\Facades\Xendit;

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

// Redirect to payment page
return redirect($payment->payment_url);
```

**Example (Fluent API):**

```php
use Laraditz\Xendit\Facades\Xendit;

$payment = Xendit::paymentRequest()
    ->amount(100000)
    ->currency('MYR')
    ->description('Payment for Order #123')
    ->ewallets('SHOPEEPAY')
    ->successUrl('https://yourapp.com/success')
    ->failureUrl('https://yourapp.com/failed')
    ->metadata(['order_id' => 123])
    ->create();
```

### `get(string $id): array`

Get payment request status by ID.

**Official API:** `GET /payment_requests/{id}`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Payment request ID |

**Example:**

```php
$paymentRequest = Xendit::paymentRequest()->get('pr_12345678');
```

### `cancel(string $id): array`

Cancel a payment request.

**Official API:** `POST /payment_requests/{id}/cancel`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Payment request ID |

**Example:**

```php
$cancelled = Xendit::paymentRequest()->cancel('pr_12345678');
```

### `simulate(string $id, array $data = []): array`

Simulate payment (test mode only).

**Official API:** `POST /payment_requests/{id}/payments/simulate`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Payment request ID |
| `data` | array | No | Simulation parameters |

**Example:**

```php
$simulated = Xendit::paymentRequest()->simulate('pr_12345678', [
    'payment_method' => [
        'type' => 'EWALLET',
        'channel_code' => 'DANA',
    ],
]);
```

## Using the Fluent Builder

The package provides a fluent builder for easier payment request creation.

### Basic Usage

```php
use Laraditz\Xendit\Facades\Xendit;

$payment = Xendit::paymentRequest()
    ->amount(100000)
    ->currency('MYR')
    ->description('Payment for Order #123')
    ->ewallets('SHOPEEPAY')
    ->successUrl('https://yourapp.com/success')
    ->failureUrl('https://yourapp.com/failed')
    ->create();

// Redirect user to payment page
return redirect($payment->payment_url);
```

### Available Builder Methods

#### `amount(float $amount)`
Set the payment amount.

```php
->amount(100000)
```

#### `currency(string $currency)`
Set the currency code.

```php
->currency('MYR')
```

#### `description(string $description)`
Set payment description.

```php
->description('Payment for Order #123')
```

#### `successUrl(string $url)`
Set success redirect URL (added to channel_properties).

```php
->successUrl('https://yourapp.com/success')
```

#### `failureUrl(string $url)`
Set failure redirect URL (added to channel_properties).

```php
->failureUrl('https://yourapp.com/failed')
```

#### `metadata(array $metadata)`
Set custom metadata.

```php
->metadata(['order_id' => 123, 'customer_id' => 456])
```

#### `for(Model $model)`
Attach payment to a model (polymorphic relationship).

```php
->for($order) // Attach to Order model
```

### Payment Method Helpers

#### `ewallets(string $channelCode = 'SHOPEEPAY')`
Set e-wallet payment channel.

```php
->ewallets('SHOPEEPAY')  // ShopeePay
->ewallets('GRABPAY')    // GrabPay
->ewallets('DANA')       // DANA
```

#### `virtualAccounts(string $channelCode = 'BCA')`
Set virtual account payment channel.

```php
->virtualAccounts('BCA')      // BCA Virtual Account
->virtualAccounts('BNI')      // BNI Virtual Account
->virtualAccounts('MANDIRI')  // Mandiri Virtual Account
```

#### `qrCode(string $channelCode = 'QRIS')`
Set QR code payment channel.

```php
->qrCode('QRIS')        // QRIS
->qrCode('QRPROMPTPAY') // PromptPay (Thailand)
```

#### `card()`
Enable card payments.

```php
->card()
```

#### `overTheCounter(string $channelCode = 'ALFAMART')`
Set over-the-counter payment channel.

```php
->overTheCounter('ALFAMART')   // Alfamart
->overTheCounter('INDOMARET')  // Indomaret
```

#### `directDebit(string $channelCode = 'BCA_KLIKPAY')`
Set direct debit payment channel.

```php
->directDebit('BCA_KLIKPAY')
```

#### `channelCode(string $channelCode)`
Set specific channel code directly.

```php
->channelCode('SHOPEEPAY')
```

#### `channelProperties(string $channelCode, array $properties)`
Set channel-specific properties.

```php
->channelProperties('SHOPEEPAY', [
    'success_return_url' => 'https://yourapp.com/success',
    'failure_return_url' => 'https://yourapp.com/failed',
])
```

## Complete Examples

### Example 1: Simple ShopeePay Payment

```php
$payment = Xendit::paymentRequest()
    ->amount(100000)
    ->currency('MYR')
    ->ewallets('SHOPEEPAY')
    ->successUrl('https://yourapp.com/success')
    ->failureUrl('https://yourapp.com/failed')
    ->create();

return redirect($payment->payment_url);
```

### Example 2: Virtual Account Payment (BCA)

```php
$payment = Xendit::paymentRequest()
    ->amount(50000)
    ->currency('IDR')
    ->virtualAccounts('BCA')
    ->description('Payment for Invoice #INV-001')
    ->create();

// Display VA number to user
echo "Please transfer to VA: " . $payment->payment_details['virtual_account_number'];
```

### Example 3: Attach to Order Model

```php
$order = Order::find(123);

$payment = Xendit::paymentRequest()
    ->amount($order->total)
    ->currency('MYR')
    ->description("Payment for Order #{$order->id}")
    ->for($order)
    ->metadata(['order_id' => $order->id])
    ->ewallets('SHOPEEPAY')
    ->successUrl(route('orders.success', $order))
    ->failureUrl(route('orders.failed', $order))
    ->create();

// Later, retrieve payments for this order
$orderPayments = $order->payments;
```

### Example 4: Card Payment with 3DS

```php
$payment = Xendit::paymentRequest()->create([
    'reference_id' => 'ORDER-' . time(),
    'amount' => 100000,
    'currency' => 'IDR',
    'country' => 'ID',
    'channel_code' => 'CARDS',
    'channel_properties' => [
        'skip_three_ds' => false,
        'success_return_url' => 'https://yourapp.com/success',
        'failure_return_url' => 'https://yourapp.com/failed',
        'card_details' => [
            'card_number' => '4000000000001091',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvn' => '123',
            'cardholder_first_name' => 'John',
            'cardholder_last_name' => 'Doe',
        ],
    ],
    'description' => 'Card payment with 3DS',
]);
```

### Example 5: QR Code Payment

```php
$payment = Xendit::paymentRequest()
    ->amount(75000)
    ->currency('THB')
    ->qrCode('QRPROMPTPAY')
    ->channelProperties('QRPROMPTPAY', [
        'qr_string_type' => 'DYNAMIC',
    ])
    ->description('Restaurant bill - Table 15')
    ->metadata([
        'table_number' => '15',
        'branch' => 'bangkok_central',
    ])
    ->create();

// Display QR code
echo $payment->payment_details['qr_string'];
```

## Response Structure

The `create()` method returns a `XenditPayment` model with the following attributes:

```php
$payment->id                    // Local database ID
$payment->external_id           // Your reference ID
$payment->xendit_id             // Xendit's payment request ID
$payment->payment_url           // URL to redirect customer
$payment->amount                // Payment amount
$payment->currency              // Currency code
$payment->status                // Payment status (enum)
$payment->payment_details       // Complete Xendit response
$payment->payable               // Related model (if attached)
$payment->created_at            // Creation timestamp
```

## Webhook Events

When a payment request status changes, Xendit sends webhook notifications. The package automatically handles these:

- `payment.succeeded` → `PaymentPaid` event
- `payment.failed` → `PaymentFailed` event
- `payment.expired` → `PaymentExpired` event

See [Webhooks Documentation](webhooks.md) for more details.
