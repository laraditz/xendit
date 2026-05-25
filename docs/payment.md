# Payment

The Payment API allows you to manage payment status, cancel payments, and capture authorized payments.

**Official Documentation:** https://docs.xendit.co/apidocs/get-payment

## Available Methods

### `get(string $id): array`

Get the status of a payment.

**Official API:** `GET /payments/{id}`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Payment ID from Xendit |

**Example:**

```php
use Laraditz\Xendit\Facades\Xendit;

$payment = Xendit::payment()->get('pay_12345678');

// Response structure
[
    'id' => 'pay_12345678',
    'reference_id' => 'ORDER-123',
    'status' => 'SUCCEEDED',
    'amount' => 100000,
    'currency' => 'MYR',
    'payment_method' => [...],
    'created_at' => '2024-01-15T10:00:00Z',
    ...
]
```

### `cancel(string $id): array`

Cancel a payment.

**Official API:** `POST /payments/{id}/cancel`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Payment ID from Xendit |

**Example:**

```php
$cancelled = Xendit::payment()->cancel('pay_12345678');

// Response
[
    'id' => 'pay_12345678',
    'status' => 'CANCELLED',
    ...
]
```

### `capture(string $id, array $data = []): array`

Capture an authorized payment.

**Official API:** `POST /payments/{id}/capture`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Payment ID from Xendit |
| `data` | array | No | Capture parameters |

**Capture Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `capture_amount` | number | No | Amount to capture (defaults to full amount) |

**Example:**

```php
// Full capture
$captured = Xendit::payment()->capture('pay_12345678');

// Partial capture
$captured = Xendit::payment()->capture('pay_12345678', [
    'capture_amount' => 50000, // Capture half the amount
]);
```

## Usage Examples

### Example 1: Check Payment Status

```php
use Laraditz\Xendit\Facades\Xendit;
use Laraditz\Xendit\Models\XenditPayment;

// Get payment from database
$localPayment = XenditPayment::where('external_id', 'ORDER-123')->first();

// Fetch latest status from Xendit
$xenditStatus = Xendit::payment()->get($localPayment->xendit_id);

// Update local database
if ($xenditStatus['status'] === 'SUCCEEDED') {
    $localPayment->markAsPaid();
}
```

### Example 2: Cancel Payment

```php
use Laraditz\Xendit\Facades\Xendit;
use Laraditz\Xendit\Models\XenditPayment;

$payment = XenditPayment::find($paymentId);

try {
    $result = Xendit::payment()->cancel($payment->xendit_id);

    // Update local status
    $payment->markAsCancelled();

    // Notify customer
    Mail::to($payment->payer_email)->send(new PaymentCancelledMail($payment));

} catch (\Exception $e) {
    Log::error('Failed to cancel payment: ' . $e->getMessage());
}
```

### Example 3: Capture Authorized Payment

```php
use Laraditz\Xendit\Facades\Xendit;

// Scenario: Pre-authorization for hotel booking
// Customer authorized $1000, but actual charge is $750

$authorizedPayment = XenditPayment::find($paymentId);

// Capture actual amount
$captured = Xendit::payment()->capture($authorizedPayment->xendit_id, [
    'capture_amount' => 750,
]);

// Update local database
$authorizedPayment->update([
    'amount' => 750,
    'status' => PaymentStatus::Paid,
    'paid_at' => now(),
]);
```

### Example 4: Payment Status Polling

```php
use Laraditz\Xendit\Facades\Xendit;
use Laraditz\Xendit\Models\XenditPayment;

// For payments without webhooks, poll for status
$payment = XenditPayment::find($paymentId);

$maxAttempts = 10;
$attempt = 0;

while ($attempt < $maxAttempts) {
    $status = Xendit::payment()->get($payment->xendit_id);

    if ($status['status'] === 'SUCCEEDED') {
        $payment->markAsPaid();
        break;
    }

    if (in_array($status['status'], ['FAILED', 'CANCELLED'])) {
        $payment->markAsFailed();
        break;
    }

    $attempt++;
    sleep(2); // Wait 2 seconds before next attempt
}
```

### Example 5: Handle Payment in Controller

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laraditz\Xendit\Facades\Xendit;
use Laraditz\Xendit\Models\XenditPayment;

class PaymentController extends Controller
{
    public function checkStatus(Request $request, $paymentId)
    {
        $payment = XenditPayment::findOrFail($paymentId);

        try {
            $xenditStatus = Xendit::payment()->get($payment->xendit_id);

            return response()->json([
                'success' => true,
                'status' => $xenditStatus['status'],
                'amount' => $xenditStatus['amount'],
                'payment_method' => $xenditStatus['payment_method']['type'] ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, $paymentId)
    {
        $payment = XenditPayment::findOrFail($paymentId);

        // Verify ownership
        if ($payment->payable_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        try {
            Xendit::payment()->cancel($payment->xendit_id);
            $payment->markAsCancelled();

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

## Payment Status Values

Xendit uses the following status values:

| Status | Description |
|--------|-------------|
| `PENDING` | Payment is awaiting completion |
| `SUCCEEDED` | Payment completed successfully |
| `FAILED` | Payment failed |
| `CANCELLED` | Payment was cancelled |
| `EXPIRED` | Payment expired |

## Best Practices

### 1. Use Webhooks Instead of Polling

```php
// ❌ Bad: Polling for status
while ($payment->isPending()) {
    $status = Xendit::payment()->get($payment->xendit_id);
    sleep(2);
}

// ✅ Good: Use webhook events
Event::listen(PaymentPaid::class, function($event) {
    $payment = $event->payment;
    // Process the payment
});
```

### 2. Handle Errors Gracefully

```php
try {
    $result = Xendit::payment()->cancel($paymentId);
} catch (\Laraditz\Xendit\Exceptions\ApiException $e) {
    // Payment might already be cancelled or completed
    Log::warning('Cannot cancel payment: ' . $e->getMessage());

    // Check actual status
    $status = Xendit::payment()->get($paymentId);

} catch (\Exception $e) {
    // Network or other errors
    Log::error('Payment API error: ' . $e->getMessage());
}
```

### 3. Keep Local Database in Sync

```php
// Always update your local database after API calls
$xenditPayment = Xendit::payment()->get($payment->xendit_id);

$payment->update([
    'status' => $this->mapXenditStatus($xenditPayment['status']),
    'payment_details' => $xenditPayment,
]);
```

### 4. Log All Payment Operations

```php
use Illuminate\Support\Facades\Log;

$result = Xendit::payment()->cancel($paymentId);

Log::info('Payment cancelled', [
    'payment_id' => $paymentId,
    'xendit_id' => $payment->xendit_id,
    'user_id' => auth()->id(),
    'timestamp' => now(),
]);
```

## Related Documentation

- [Payment Request](payment-request.md) - Creating payments
- [Webhooks](webhooks.md) - Handling payment events
- [Refunds](refund.md) - Processing refunds
