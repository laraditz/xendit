# Webhooks

Xendit sends webhook notifications when events happen in your account, such as successful payments, expired invoices, or completed refunds. This package automatically handles webhook verification, logging, and event dispatching.

**Official Documentation:** https://docs.xendit.co/apidocs/webhooks

## Webhook Setup

### 1. Configure Webhook URL

The package automatically registers a webhook endpoint at:

```
https://yourapp.com/xendit/webhook
```

Add this URL to your Xendit dashboard:
1. Go to Settings → Webhooks in your Xendit dashboard
2. Add the webhook URL
3. Select the events you want to receive
4. Copy the webhook verification token

### 2. Set Webhook Secret

Add the webhook verification token to your `.env`:

```env
XENDIT_WEBHOOK_SECRET=your-webhook-verification-token-from-xendit-dashboard
```

### 3. Webhook Verification

The package automatically verifies all incoming webhooks using the `VerifyXenditWebhook` middleware. Invalid webhooks are rejected with a 403 response.

## Available Events

The package dispatches Laravel events for all webhook notifications:

### Payment Events

| Event | Description | Triggered When |
|-------|-------------|----------------|
| `PaymentPaid` | Payment completed successfully | Customer completes payment |
| `PaymentFailed` | Payment failed | Payment attempt fails |
| `PaymentExpired` | Payment expired | Payment link/request expires |

### Payment Token Events

| Event | Description | Triggered When |
|-------|-------------|----------------|
| `PaymentTokenCreated` | Payment token created | Saved payment method created |
| `PaymentTokenActivated` | Payment token activated | Saved payment method verified |

### Refund Events

| Event | Description | Triggered When |
|-------|-------------|----------------|
| `RefundCreated` | Refund created in your system | After `Xendit::refund()->create()` succeeds |
| `RefundSucceeded` | Refund completed | Webhook `refund.succeeded` received |
| `RefundFailed` | Refund failed | Webhook `refund.failed` received |

All refund events expose `$event->refund` — a `XenditRefund` model instance — except `RefundSucceeded`/`RefundFailed`, which expose the full webhook envelope as `$event->payload`.

### Session Events

| Event | Description | Triggered When |
|-------|-------------|----------------|
| `SessionCreated` | Session created in your system | After `Xendit::session()->create()` succeeds |
| `SessionCompleted` | Payment collected | Webhook `payment_session.completed` received |
| `SessionExpired` | Session timed out | Webhook `payment_session.expired` received |
| `SessionCanceled` | Session canceled | After `Xendit::session()->cancel()` is called |

All session events expose `$event->session` — an `XenditSession` model instance.

### Generic Event

| Event | Description | Triggered When |
|-------|-------------|----------------|
| `WebhookReceived` | Any webhook received | Every webhook (dispatched first) |

## Listening to Webhook Events

### Register Event Listeners

In your `EventServiceProvider`:

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
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
use Laraditz\Xendit\Events\WebhookReceived;
use App\Listeners\SendPaymentConfirmation;
use App\Listeners\CancelExpiredOrder;
use App\Listeners\NotifyPaymentFailure;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentPaid::class => [
            SendPaymentConfirmation::class,
            ProcessOrder::class,
            UpdateInventory::class,
        ],
        PaymentExpired::class => [
            CancelExpiredOrder::class,
        ],
        PaymentFailed::class => [
            NotifyPaymentFailure::class,
        ],
        RefundCreated::class => [
            LogRefundCreated::class,
        ],
        RefundSucceeded::class => [
            ProcessRefund::class,
            SendRefundConfirmation::class,
        ],
        RefundFailed::class => [
            NotifyRefundFailure::class,
        ],
        PaymentTokenCreated::class => [
            LogPaymentToken::class,
        ],
        SessionCompleted::class => [
            FulfillOrder::class,
        ],
        SessionExpired::class => [
            CancelPendingOrder::class,
        ],
        SessionCanceled::class => [
            ReleaseReservedStock::class,
        ],
        WebhookReceived::class => [
            LogWebhookEvent::class,
        ],
    ];
}
```

## Event Listener Examples

### Example 1: Handle Successful Payment

```php
<?php

namespace App\Listeners;

use Laraditz\Xendit\Events\PaymentPaid;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmationMail;

class ProcessOrder
{
    public function handle(PaymentPaid $event)
    {
        $payment = $event->payment;

        // Get the related model (polymorphic)
        $order = $payment->payable;

        if (!$order instanceof Order) {
            return;
        }

        // Update order status
        $order->update([
            'status' => 'confirmed',
            'paid_at' => now(),
        ]);

        // Send confirmation email
        Mail::to($order->customer_email)->send(
            new OrderConfirmationMail($order)
        );

        // Update inventory
        foreach ($order->items as $item) {
            $item->product->decrement('stock', $item->quantity);
        }

        // Log the payment
        \Log::info('Payment processed successfully', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => $payment->amount,
        ]);
    }
}
```

### Example 2: Handle Expired Payment

```php
<?php

namespace App\Listeners;

use Laraditz\Xendit\Events\PaymentExpired;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentExpiredMail;

class CancelExpiredOrder
{
    public function handle(PaymentExpired $event)
    {
        $payment = $event->payment;
        $order = $payment->payable;

        if (!$order instanceof Order) {
            return;
        }

        // Cancel the order
        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'Payment expired',
        ]);

        // Release reserved stock
        foreach ($order->items as $item) {
            $item->product->increment('stock', $item->quantity);
        }

        // Notify customer
        Mail::to($order->customer_email)->send(
            new PaymentExpiredMail($order)
        );

        \Log::info('Order cancelled due to payment expiry', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
        ]);
    }
}
```

### Example 3: Handle Failed Payment

```php
<?php

namespace App\Listeners;

use Laraditz\Xendit\Events\PaymentFailed;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentFailedMail;

class NotifyPaymentFailure
{
    public function handle(PaymentFailed $event)
    {
        $payment = $event->payment;
        $order = $payment->payable;

        if (!$order instanceof Order) {
            return;
        }

        // Log the failure
        $order->paymentAttempts()->create([
            'status' => 'failed',
            'payment_id' => $payment->id,
            'attempted_at' => now(),
        ]);

        // Check retry count
        $failedAttempts = $order->paymentAttempts()->where('status', 'failed')->count();

        if ($failedAttempts >= 3) {
            // Too many failures, cancel order
            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'Payment failed after multiple attempts',
            ]);
        }

        // Notify customer
        Mail::to($order->customer_email)->send(
            new PaymentFailedMail($order, $failedAttempts)
        );

        \Log::warning('Payment failed', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'attempt_count' => $failedAttempts,
        ]);
    }
}
```

### Example 4: Handle Refund Success

```php
<?php

namespace App\Listeners;

use Laraditz\Xendit\Events\RefundSucceeded;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use App\Mail\RefundCompletedMail;

class ProcessRefund
{
    public function handle(RefundSucceeded $event)
    {
        // $event->payload is the full webhook envelope; refund fields live under 'data'
        $refundData = $event->payload['data'];

        // Get order from metadata
        $orderId = $refundData['metadata']['order_id'] ?? null;

        if (!$orderId) {
            \Log::warning('Refund succeeded but no order_id in metadata', [
                'refund_id' => $refundData['id'],
            ]);
            return;
        }

        $order = Order::find($orderId);

        if (!$order) {
            \Log::error('Order not found for refund', [
                'refund_id' => $refundData['id'],
                'order_id' => $orderId,
            ]);
            return;
        }

        // Update order
        $order->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_amount' => $refundData['amount'],
        ]);

        // Restore inventory if needed
        if ($refundData['metadata']['restore_inventory'] ?? false) {
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }
        }

        // Send confirmation
        Mail::to($order->customer_email)->send(
            new RefundCompletedMail($order, $refundData)
        );

        \Log::info('Refund processed successfully', [
            'order_id' => $order->id,
            'refund_id' => $refundData['id'],
            'amount' => $refundData['amount'],
        ]);
    }
}
```

### Example 5: Handle Payment Token Created

```php
<?php

namespace App\Listeners;

use Laraditz\Xendit\Events\PaymentTokenCreated;
use App\Models\SavedPaymentMethod;
use App\Models\User;

class LogPaymentToken
{
    public function handle(PaymentTokenCreated $event)
    {
        $tokenData = $event->payload;

        // Extract user ID from customer_id
        $customerId = $tokenData['customer_id'];
        $userId = str_replace('user-', '', $customerId);

        $user = User::find($userId);

        if (!$user) {
            return;
        }

        // Store payment token reference
        $user->savedPaymentMethods()->create([
            'token_id' => $tokenData['id'],
            'type' => strtolower($tokenData['type']),
            'status' => $tokenData['status'],
            'metadata' => [
                'card' => $tokenData['card'] ?? null,
                'direct_debit' => $tokenData['direct_debit'] ?? null,
            ],
        ]);

        \Log::info('Payment token saved', [
            'user_id' => $user->id,
            'token_id' => $tokenData['id'],
            'type' => $tokenData['type'],
        ]);
    }
}
```

### Example 6: Generic Webhook Logger

```php
<?php

namespace App\Listeners;

use Laraditz\Xendit\Events\WebhookReceived;
use Illuminate\Support\Facades\Log;

class LogWebhookEvent
{
    public function handle(WebhookReceived $event)
    {
        Log::channel('xendit')->info('Webhook received', [
            'event_type' => $event->eventType,
            'external_id' => $event->payload['external_id'] ?? null,
            'xendit_id' => $event->payload['id'] ?? null,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
```

## Event Properties

### PaymentPaid Event

```php
$event->payment; // XenditPayment model instance

// Access related model
$order = $event->payment->payable;

// Payment details
$amount = $event->payment->amount;
$status = $event->payment->status;
$xenditId = $event->payment->xendit_id;
```

### PaymentExpired Event

```php
$event->payment; // XenditPayment model instance
```

### PaymentFailed Event

```php
$event->payment; // XenditPayment model instance
```

### RefundCreated Event

```php
$event->refund; // XenditRefund model instance

$event->refund->refund_id;   // Xendit's refund ID
$event->refund->status;      // RefundStatus::Pending (typically, right after creation)
$event->refund->payment;     // XenditPayment relation, if resolved
```

### RefundSucceeded Event

```php
$event->payload; // Full webhook envelope: {event, business_id, created, data}
$event->payload['data']; // The refund object

// Example payload structure
[
    'event' => 'refund.succeeded',
    'business_id' => 'biz-12345678',
    'created' => '2026-06-08T02:17:33.376Z',
    'data' => [
        'id' => 'rfd-12345678',
        'payment_request_id' => 'pr-12345678',
        'amount' => 100000,
        'currency' => 'MYR',
        'status' => 'SUCCEEDED',
        'reason' => 'REQUESTED_BY_CUSTOMER',
        'failure_code' => null,
        'refund_fee_amount' => 0,
        'metadata' => [...],
    ],
]
```

### RefundFailed Event

```php
$event->payload; // Full webhook envelope: {event, business_id, created, data}
$event->payload['data']; // The refund object

// Example payload structure
[
    'event' => 'refund.failed',
    'business_id' => 'biz-12345678',
    'created' => '2026-06-08T02:17:33.376Z',
    'data' => [
        'id' => 'rfd-12345678',
        'payment_request_id' => 'pr-12345678',
        'amount' => 100000,
        'currency' => 'MYR',
        'status' => 'FAILED',
        'reason' => 'REQUESTED_BY_CUSTOMER',
        'failure_code' => 'INSUFFICIENT_BALANCE',
        'refund_fee_amount' => null,
        'metadata' => [...],
    ],
]
```

### PaymentTokenCreated Event

```php
$event->payload; // Array of token data

// Example payload structure
[
    'id' => 'pt_12345678',
    'customer_id' => 'user-123',
    'type' => 'CARD',
    'status' => 'ACTIVE',
    'card' => [
        'last4' => '0002',
        'brand' => 'VISA',
    ],
]
```

### SessionCompleted / SessionExpired / SessionCanceled Events

```php
$event->session; // XenditSession model instance

// Access fields
$session = $event->session;
$session->reference_id;       // your reference
$session->payment_session_id; // Xendit's session ID
$session->status;             // SessionStatus enum
$session->amount;             // decimal
$session->payment_link_url;   // redirect URL
$session->completed_at;       // set on completion
$session->canceled_at;        // set on cancellation
$session->payable;            // polymorphic: your Order, User, etc.
$session->xenditCustomer;     // XenditCustomer model (if customer_id set)
```

### WebhookReceived Event

```php
$event->eventType; // String: 'payment.succeeded', 'refund.succeeded', etc.
$event->payload;   // Array: Full webhook payload
```

## Webhook Logging

All webhooks are automatically logged to the `xendit_webhook_logs` table with:

- Event type
- External ID
- Xendit ID
- Full payload
- Processing status
- Error messages (if failed)

### Query Webhook Logs

```php
use Laraditz\Xendit\Models\XenditWebhookLog;

// Get all webhooks
$logs = XenditWebhookLog::latest()->get();

// Get processed webhooks
$processed = XenditWebhookLog::processed()->get();

// Get failed webhooks
$failed = XenditWebhookLog::failed()->get();

// Get webhooks by event type
$paymentWebhooks = XenditWebhookLog::where('event_type', 'payment.succeeded')->get();

// Get webhooks for specific order
$orderWebhooks = XenditWebhookLog::where('external_id', 'ORDER-123')->get();
```

## Testing Webhooks Locally

### 1. Using ngrok

```bash
# Install ngrok
npm install -g ngrok

# Start ngrok tunnel
ngrok http 80

# Use the HTTPS URL in Xendit dashboard
# Example: https://abc123.ngrok.io/xendit/webhook
```

### 2. Manual Webhook Testing

Send a test webhook using cURL:

```bash
curl -X POST https://yourapp.com/xendit/webhook \
  -H "Content-Type: application/json" \
  -H "x-callback-token: your-webhook-secret" \
  -d '{
    "event": "payment.succeeded",
    "id": "pay_test123",
    "reference_id": "ORDER-123",
    "amount": 100000,
    "currency": "MYR",
    "status": "SUCCEEDED"
  }'
```

### 3. Xendit Test Mode

Xendit provides a test mode for simulating webhooks. Use the `simulate` method:

```php
use Laraditz\Xendit\Facades\Xendit;

// Simulate payment success (test mode only)
$result = Xendit::paymentRequest()->simulate($paymentRequestId, [
    'payment_method' => [
        'type' => 'EWALLET',
        'channel_code' => 'DANA',
    ],
]);
```

## Best Practices

### 1. Idempotency

Always check if the webhook has already been processed:

```php
public function handle(PaymentPaid $event)
{
    $payment = $event->payment;

    // Check if already processed
    if ($payment->payable->status === 'confirmed') {
        \Log::info('Payment already processed, skipping', [
            'payment_id' => $payment->id,
        ]);
        return;
    }

    // Process the payment...
}
```

### 2. Queue Listeners

For long-running operations, implement `ShouldQueue`:

```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Laraditz\Xendit\Events\PaymentPaid;

class ProcessOrder implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'webhooks';
    public $tries = 3;
    public $timeout = 60;

    public function handle(PaymentPaid $event)
    {
        // Process in background
    }
}
```

### 3. Error Handling

Always wrap webhook processing in try-catch:

```php
public function handle(PaymentPaid $event)
{
    try {
        $payment = $event->payment;
        $order = $payment->payable;

        // Process order...

    } catch (\Exception $e) {
        \Log::error('Failed to process payment webhook', [
            'payment_id' => $payment->id ?? null,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Optionally notify admin
        \Mail::to(config('mail.admin'))->send(
            new WebhookFailedMail($event, $e)
        );

        throw $e; // Re-throw for webhook retry
    }
}
```

### 4. Webhook Retry Logic

Xendit will retry failed webhooks. Handle this gracefully:

```php
public function handle(PaymentPaid $event)
{
    $payment = $event->payment;

    // Use database transactions for atomicity
    \DB::transaction(function () use ($payment) {
        $order = $payment->payable;

        // Lock the order to prevent concurrent updates
        $order->lockForUpdate();

        if ($order->status === 'confirmed') {
            return; // Already processed
        }

        $order->update(['status' => 'confirmed']);

        // Send email only once
        if (!$order->confirmation_sent_at) {
            \Mail::to($order->customer_email)->send(
                new OrderConfirmationMail($order)
            );

            $order->update(['confirmation_sent_at' => now()]);
        }
    });
}
```

### 5. Monitor Webhook Health

Create a scheduled job to check webhook processing:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laraditz\Xendit\Models\XenditWebhookLog;
use Illuminate\Support\Facades\Mail;

class CheckWebhookHealth extends Command
{
    protected $signature = 'webhooks:check-health';

    public function handle()
    {
        // Check for failed webhooks in last hour
        $failedCount = XenditWebhookLog::failed()
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($failedCount > 10) {
            Mail::to(config('mail.admin'))->send(
                new WebhookHealthAlert($failedCount)
            );
        }

        // Check for unprocessed webhooks older than 5 minutes
        $stuckCount = XenditWebhookLog::pending()
            ->where('created_at', '<', now()->subMinutes(5))
            ->count();

        if ($stuckCount > 0) {
            Mail::to(config('mail.admin'))->send(
                new WebhookStuckAlert($stuckCount)
            );
        }

        $this->info("Webhook health check complete. Failed: {$failedCount}, Stuck: {$stuckCount}");
    }
}
```

## Security Considerations

1. **Verify Signatures**: The package automatically verifies webhook signatures using the `VerifyXenditWebhook` middleware
2. **Use HTTPS**: Always use HTTPS for webhook URLs in production
3. **Validate Payload**: Always validate webhook data before processing
4. **Idempotency**: Implement idempotent webhook handlers to prevent duplicate processing
5. **Rate Limiting**: Consider rate limiting webhook endpoints to prevent abuse
6. **Logging**: Log all webhooks for audit and debugging purposes

## Troubleshooting

### Webhooks Not Received

1. Check webhook URL is correct in Xendit dashboard
2. Verify `XENDIT_WEBHOOK_SECRET` matches dashboard
3. Ensure application is accessible (not localhost without ngrok)
4. Check server firewall allows incoming requests

### Webhooks Failing

1. Check webhook logs: `XenditWebhookLog::failed()->latest()->get()`
2. Review error messages in `error_message` column
3. Check application logs for exceptions
4. Verify event listeners are registered

### Duplicate Processing

1. Implement idempotency checks in listeners
2. Use database transactions with locks
3. Check if webhook is already processed before taking action

## Related Documentation

- [Payment](payment.md) - Managing payments
- [Payment Request](payment-request.md) - Creating payments
- [Refund](refund.md) - Processing refunds
- [Payment Token](payment-token.md) - Saved payment methods
- [Customer](customer.md) - Managing Xendit customers
- [Session](session.md) - Creating payment sessions
