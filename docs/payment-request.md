# Payment Request

The Payment Request API is Xendit's unified payment method that allows you to accept payments from multiple payment channels in a single integration.

**Official Documentation:** https://docs.xendit.co/apidocs/create-payment-request

## Available Methods

### `create(array $data): array`

Creates a new payment request.

**Official API:** `POST /payment_requests`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `reference_id` | string | Yes | Your unique reference ID |
| `amount` | number | Yes | Payment amount |
| `currency` | string | Yes | Currency code (IDR, PHP, THB, VND, MYR) |
| `customer` | object | No | Customer information |
| `payment_method` | object | No | Payment method configuration |
| `description` | string | No | Payment description |
| `success_redirect_url` | string | No | Success redirect URL |
| `failure_redirect_url` | string | No | Failure redirect URL |
| `metadata` | object | No | Custom metadata |

**Example:**

```php
use Laraditz\Xendit\Facades\Xendit;

$response = Xendit::paymentRequest()->create([
    'reference_id' => 'ORDER-123',
    'amount' => 100000,
    'currency' => 'MYR',
    'customer' => [
        'email' => 'customer@example.com',
        'mobile_number' => '+60123456789',
        'given_names' => 'John',
    ],
    'payment_method' => [
        'type' => 'PAYMENT_METHOD_LIST',
        'payment_methods' => [
            ['type' => 'EWALLET', 'reusability' => ['type' => 'ONE_TIME_USE']],
            ['type' => 'VIRTUAL_ACCOUNT', 'reusability' => ['type' => 'ONE_TIME_USE']],
        ],
        'reusability' => 'ONE_TIME_USE',
    ],
    'success_redirect_url' => 'https://yourapp.com/success',
    'failure_redirect_url' => 'https://yourapp.com/failed',
]);
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
    ->description('Payment for Order #123')
    ->email('customer@example.com')
    ->phone('+60123456789')
    ->allMethods()
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

#### `email(string $email)`
Set customer email.

```php
->email('customer@example.com')
```

#### `phone(string $phone)`
Set customer phone number.

```php
->phone('+60123456789')
```

#### `givenNames(string $givenNames)`
Set customer given names.

```php
->givenNames('John Doe')
```

#### `customer(array $customer)`
Set complete customer information.

```php
->customer([
    'email' => 'customer@example.com',
    'mobile_number' => '+60123456789',
    'given_names' => 'John',
    'surname' => 'Doe',
    'type' => 'INDIVIDUAL',
])
```

#### `successUrl(string $url)`
Set success redirect URL.

```php
->successUrl('https://yourapp.com/success')
```

#### `failureUrl(string $url)`
Set failure redirect URL.

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

#### `allMethods()`
Enable all available payment methods.

```php
->allMethods()
```

#### `ewallets()`
Enable e-wallet payments (DANA, OVO, ShopeePay, etc.).

```php
->ewallets()
```

#### `virtualAccounts()`
Enable virtual account payments (BCA, BNI, BRI, etc.).

```php
->virtualAccounts()
```

#### `qrCode()`
Enable QR code payments (QRIS).

```php
->qrCode()
```

#### `overTheCounter()`
Enable over-the-counter payments (Alfamart, Indomaret).

```php
->overTheCounter()
```

#### `card()`
Enable card payments.

```php
->card()
```

#### `directDebit()`
Enable direct debit payments.

```php
->directDebit()
```

#### `paymentMethod(string $type, array $reusability = null)`
Add a custom payment method.

```php
->paymentMethod('EWALLET', ['type' => 'ONE_TIME_USE'])
```

#### `paymentMethods(array $methods)`
Set multiple payment methods at once.

```php
->paymentMethods([
    ['type' => 'EWALLET', 'reusability' => ['type' => 'ONE_TIME_USE']],
    ['type' => 'VIRTUAL_ACCOUNT', 'reusability' => ['type' => 'ONE_TIME_USE']],
])
```

#### `channelProperties(string $channelCode, array $properties)`
Set channel-specific properties.

```php
->channelProperties('SHOPEEPAY', [
    'success_return_url' => 'https://yourapp.com/success',
])
```

#### `items(array $items)`
Set line items.

```php
->items([
    [
        'name' => 'Product 1',
        'quantity' => 2,
        'price' => 50000,
    ],
    [
        'name' => 'Product 2',
        'quantity' => 1,
        'price' => 100000,
    ],
])
```

## Complete Examples

### Example 1: Simple Payment with All Methods

```php
$payment = Xendit::paymentRequest()
    ->amount(100000)
    ->email('customer@example.com')
    ->allMethods()
    ->create();

return redirect($payment->payment_url);
```

### Example 2: E-Wallets Only

```php
$payment = Xendit::paymentRequest()
    ->amount(50000)
    ->email('customer@example.com')
    ->phone('+60123456789')
    ->ewallets()
    ->successUrl('https://yourapp.com/success')
    ->create();
```

### Example 3: Attach to Order Model

```php
$order = Order::find(123);

$payment = Xendit::paymentRequest()
    ->amount($order->total)
    ->description("Payment for Order #{$order->id}")
    ->email($order->customer_email)
    ->for($order)
    ->metadata(['order_id' => $order->id])
    ->allMethods()
    ->create();

// Later, retrieve payments for this order
$orderPayments = $order->payments;
```

### Example 4: With Line Items

```php
$payment = Xendit::paymentRequest()
    ->amount(150000)
    ->email('customer@example.com')
    ->items([
        [
            'name' => 'Product A',
            'quantity' => 2,
            'price' => 50000,
            'category' => 'Electronics',
        ],
        [
            'name' => 'Product B',
            'quantity' => 1,
            'price' => 50000,
            'category' => 'Accessories',
        ],
    ])
    ->allMethods()
    ->create();
```

### Example 5: Custom Payment Methods

```php
$payment = Xendit::paymentRequest()
    ->amount(100000)
    ->email('customer@example.com')
    ->paymentMethods([
        [
            'type' => 'EWALLET',
            'ewallet' => [
                'channel_code' => 'SHOPEEPAY',
            ],
            'reusability' => ['type' => 'ONE_TIME_USE'],
        ],
        [
            'type' => 'VIRTUAL_ACCOUNT',
            'virtual_account' => [
                'channel_code' => 'BCA',
            ],
            'reusability' => ['type' => 'ONE_TIME_USE'],
        ],
    ])
    ->create();
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
