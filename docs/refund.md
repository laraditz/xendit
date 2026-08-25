# Refund

The Refund API allows you to refund payments that have been successfully completed. You can process full or partial refunds for customer returns, cancellations, or disputes.

**Official Documentation:** https://docs.xendit.co/apidocs/refund-payment-request

## Available Methods

### `create(array $data): XenditRefund`

Create a refund for a completed payment. Persists a local `XenditRefund` record immediately from the API response — it does not wait for the `refund.succeeded`/`refund.failed` webhook to appear.

**Official API:** `POST /refunds`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `payment_request_id` | string | Yes | Xendit Payment Request ID to refund (`payment_id` is deprecated by Xendit) |
| `currency` | string | No | ISO 4217 currency code |
| `amount` | number | No | Refund amount (defaults to full amount) |
| `reason` | string | Yes | One of `RefundReason`: `FRAUDULENT`, `DUPLICATE`, `REQUESTED_BY_CUSTOMER`, `CANCELLATION`, `OTHERS` |
| `reference_id` | string | No | Your unique reference ID |
| `metadata` | object | No | Custom metadata |

**Example:**

```php
use Laraditz\Xendit\Enums\RefundReason;
use Laraditz\Xendit\Facades\Xendit;

// Full refund
$refund = Xendit::refund()->create([
    'payment_request_id' => 'pr-12345678',
    'reason' => RefundReason::RequestedByCustomer->value,
    'reference_id' => 'REFUND-001',
]);

// Partial refund
$refund = Xendit::refund()->create([
    'payment_request_id' => 'pr-12345678',
    'amount' => 50000, // Refund half
    'reason' => RefundReason::RequestedByCustomer->value,
    'reference_id' => 'REFUND-002',
    'metadata' => [
        'order_id' => 123,
        'returned_items' => ['item-1', 'item-2'],
    ],
]);

// $refund is a XenditRefund model, mapped from this raw Xendit response shape:
[
    'id' => 'rfd-12345678',
    'payment_request_id' => 'pr-12345678',
    'payment_id' => null, // deprecated
    'invoice_id' => null, // deprecated
    'payment_method_type' => 'EWALLET',
    'reference_id' => 'REFUND-002',
    'channel_code' => 'GCASH',
    'currency' => 'MYR',
    'amount' => 50000,
    'status' => 'PENDING',
    'reason' => 'REQUESTED_BY_CUSTOMER',
    'failure_code' => null,
    'refund_fee_amount' => 0,
    'metadata' => [...],
    'created' => '2024-01-15T10:00:00Z',
    'updated' => '2024-01-15T10:00:00Z',
]

// Access via model property, not array key:
$refund->refund_id;   // 'rfd-12345678' — Xendit's refund ID
$refund->id;          // the LOCAL database row's own primary key — NOT the same value
$refund->status;      // RefundStatus::Pending
$refund->reason;      // RefundReason::RequestedByCustomer
$refund->amount;      // 50000
```

## Usage Examples

### Example 1: Full Order Refund

```php
use Laraditz\Xendit\Enums\RefundReason;
use Laraditz\Xendit\Facades\Xendit;
use Laraditz\Xendit\Models\XenditPayment;

public function refundOrder(Request $request, $orderId)
{
    $order = Order::findOrFail($orderId);

    // Verify order is paid
    if ($order->status !== 'paid') {
        throw new \Exception('Order is not paid yet');
    }

    // Get the payment record
    $payment = XenditPayment::where('payable_type', Order::class)
        ->where('payable_id', $order->id)
        ->where('status', PaymentStatus::Paid)
        ->firstOrFail();

    // Validate the incoming reason against Xendit's allowed enum values
    $reason = RefundReason::from($request->reason);

    // Create refund
    $refund = Xendit::refund()->create([
        'payment_request_id' => $payment->xendit_id,
        'reason' => $reason->value,
        'reference_id' => "REFUND-{$order->id}-" . now()->timestamp,
        'metadata' => [
            'order_id' => $order->id,
            'refund_requested_by' => auth()->id(),
            'refund_reason' => $request->reason,
        ],
    ]);

    // Update order status
    $order->update([
        'status' => 'refund_pending',
        'refund_id' => $refund->refund_id,
        'refund_requested_at' => now(),
    ]);

    // Log the refund request
    Log::info('Refund requested', [
        'order_id' => $order->id,
        'refund_id' => $refund->refund_id,
        'amount' => $payment->amount,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Refund request submitted successfully',
        'refund_id' => $refund->refund_id,
    ]);
}
```

### Example 2: Partial Refund for Returned Items

```php
use Laraditz\Xendit\Enums\RefundReason;
use Laraditz\Xendit\Facades\Xendit;

public function processReturn(Request $request, $orderId)
{
    $order = Order::findOrFail($orderId);
    $returnItems = $request->input('items'); // Array of item IDs

    // Calculate refund amount
    $refundAmount = 0;
    $returnedItemDetails = [];

    foreach ($returnItems as $itemId) {
        $item = $order->items()->findOrFail($itemId);
        $refundAmount += $item->price * $item->quantity;
        $returnedItemDetails[] = [
            'product' => $item->product->name,
            'quantity' => $item->quantity,
            'amount' => $item->price * $item->quantity,
        ];

        // Mark item as returned
        $item->update(['status' => 'returned']);
    }

    // Get payment
    $payment = $order->payments()->where('status', PaymentStatus::Paid)->first();

    // Create partial refund
    // Note: Xendit's reason enum has no dedicated "partial return" value —
    // REQUESTED_BY_CUSTOMER is the closest fit; the human-readable detail
    // lives in metadata instead.
    $refund = Xendit::refund()->create([
        'payment_request_id' => $payment->xendit_id,
        'amount' => $refundAmount,
        'reason' => RefundReason::RequestedByCustomer->value,
        'reference_id' => "RETURN-{$order->id}-" . now()->timestamp,
        'metadata' => [
            'order_id' => $order->id,
            'returned_items' => $returnedItemDetails,
            'partial_refund' => true,
            'notes' => 'Partial return - customer returned items',
        ],
    ]);

    // Create return record
    $order->returns()->create([
        'refund_id' => $refund->refund_id,
        'amount' => $refundAmount,
        'status' => 'pending',
        'items' => $returnedItemDetails,
    ]);

    return redirect()->route('orders.show', $order)
        ->with('success', "Refund of " . formatCurrency($refundAmount) . " has been initiated");
}
```

### Example 3: Automatic Refund on Cancellation

```php
use Laraditz\Xendit\Enums\RefundReason;
use Laraditz\Xendit\Facades\Xendit;
use App\Models\Order;

class CancelOrderListener
{
    public function handle(OrderCancelled $event)
    {
        $order = $event->order;

        // Check if order is paid
        if ($order->status !== 'paid') {
            return;
        }

        // Get the payment
        $payment = $order->payments()
            ->where('status', PaymentStatus::Paid)
            ->first();

        if (!$payment) {
            return;
        }

        try {
            // Automatically process refund
            $refund = Xendit::refund()->create([
                'payment_request_id' => $payment->xendit_id,
                'reason' => RefundReason::Cancellation->value,
                'reference_id' => "CANCEL-{$order->id}-" . now()->timestamp,
                'metadata' => [
                    'order_id' => $order->id,
                    'cancelled_by' => $order->cancelled_by,
                    'cancellation_reason' => $order->cancellation_reason,
                    'auto_refund' => true,
                ],
            ]);

            $order->update([
                'refund_id' => $refund->refund_id,
                'refund_status' => 'pending',
            ]);

            // Notify customer
            Mail::to($order->customer_email)->send(
                new RefundInitiatedMail($order, $refund)
            );

            Log::info('Auto-refund initiated for cancelled order', [
                'order_id' => $order->id,
                'refund_id' => $refund->refund_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process auto-refund', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            // Notify admin about failed auto-refund
            Mail::to(config('mail.admin'))->send(
                new RefundFailedMail($order, $e->getMessage())
            );
        }
    }
}
```

### Example 4: Refund Request Management

```php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Laraditz\Xendit\Facades\Xendit;
use App\Models\RefundRequest;

class RefundController extends Controller
{
    public function index()
    {
        $refunds = RefundRequest::with(['order', 'requestedBy'])
            ->latest()
            ->paginate(20);

        return view('admin.refunds.index', compact('refunds'));
    }

    public function approve(Request $request, $refundRequestId)
    {
        $refundRequest = RefundRequest::findOrFail($refundRequestId);
        $order = $refundRequest->order;
        $payment = $order->payments()->paid()->first();

        // Process refund with Xendit
        // $refundRequest->reason is validated against RefundReason at
        // creation time (see Example 6), so it's safe to pass through here.
        $refund = Xendit::refund()->create([
            'payment_request_id' => $payment->xendit_id,
            'amount' => $refundRequest->amount,
            'reason' => $refundRequest->reason,
            'reference_id' => "REFUND-{$refundRequest->id}",
            'metadata' => [
                'refund_request_id' => $refundRequest->id,
                'order_id' => $order->id,
                'approved_by' => auth()->id(),
                'approved_at' => now()->toISOString(),
            ],
        ]);

        // Update refund request
        $refundRequest->update([
            'status' => 'approved',
            'xendit_refund_id' => $refund->refund_id,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Update order
        $order->update([
            'refund_status' => 'processing',
        ]);

        // Notify customer
        Mail::to($order->customer_email)->send(
            new RefundApprovedMail($order, $refundRequest)
        );

        return redirect()->back()
            ->with('success', 'Refund approved and processed');
    }

    public function reject(Request $request, $refundRequestId)
    {
        $refundRequest = RefundRequest::findOrFail($refundRequestId);

        $refundRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        // Notify customer
        Mail::to($refundRequest->order->customer_email)->send(
            new RefundRejectedMail($refundRequest)
        );

        return redirect()->back()
            ->with('success', 'Refund request rejected');
    }
}
```

### Example 5: Handle Refund Webhook

```php
namespace App\Listeners;

use Laraditz\Xendit\Events\RefundSucceeded;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessRefundSuccess
{
    public function handle(RefundSucceeded $event)
    {
        // $event->payload is the full webhook envelope ({event, business_id,
        // created, data}); the refund fields live under 'data'.
        $refundData = $event->payload['data'];

        // Find the order
        $orderId = $refundData['metadata']['order_id'] ?? null;

        if (!$orderId) {
            Log::warning('Refund succeeded but no order_id in metadata', [
                'refund_id' => $refundData['id'],
            ]);
            return;
        }

        $order = Order::find($orderId);

        if (!$order) {
            Log::error('Order not found for refund', [
                'refund_id' => $refundData['id'],
                'order_id' => $orderId,
            ]);
            return;
        }

        // Update order status
        $order->update([
            'status' => 'refunded',
            'refund_status' => 'completed',
            'refunded_at' => now(),
            'refund_amount' => $refundData['amount'],
        ]);

        // Log the successful refund
        Log::info('Refund completed successfully', [
            'order_id' => $order->id,
            'refund_id' => $refundData['id'],
            'amount' => $refundData['amount'],
        ]);

        // Notify customer
        Mail::to($order->customer_email)->send(
            new RefundCompletedMail($order, $refundData)
        );

        // If partial refund, check if there are more items to refund
        if ($refundData['metadata']['partial_refund'] ?? false) {
            $this->checkRemainingRefunds($order);
        }
    }

    protected function checkRemainingRefunds(Order $order)
    {
        $totalRefunded = $order->returns()->sum('amount');
        $orderTotal = $order->total;

        if ($totalRefunded >= $orderTotal) {
            $order->update(['status' => 'fully_refunded']);
        }
    }
}
```

### Example 6: Customer-Initiated Refund Request

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Laraditz\Xendit\Enums\RefundReason;
use App\Models\Order;

class CustomerRefundController extends Controller
{
    public function create($orderId)
    {
        $order = auth()->user()
            ->orders()
            ->findOrFail($orderId);

        // Check if order is eligible for refund
        if (!$this->isEligibleForRefund($order)) {
            return redirect()->back()
                ->withError('This order is not eligible for refund');
        }

        return view('refunds.create', compact('order'));
    }

    public function store(Request $request, $orderId)
    {
        $request->validate([
            'reason' => ['required', new Enum(RefundReason::class)],
            'items' => 'nullable|array',
            'items.*' => 'exists:order_items,id',
        ]);

        $order = auth()->user()
            ->orders()
            ->findOrFail($orderId);

        if (!$this->isEligibleForRefund($order)) {
            return redirect()->back()
                ->withError('This order is not eligible for refund');
        }

        // Calculate refund amount
        $refundAmount = $order->total;
        $items = null;

        if ($request->has('items')) {
            $refundAmount = 0;
            $items = [];

            foreach ($request->items as $itemId) {
                $item = $order->items()->findOrFail($itemId);
                $refundAmount += $item->total;
                $items[] = $itemId;
            }
        }

        // Create refund request
        $refundRequest = $order->refundRequests()->create([
            'user_id' => auth()->id(),
            'amount' => $refundAmount,
            'reason' => $request->reason,
            'status' => 'pending',
            'items' => $items,
        ]);

        // Notify admin
        Mail::to(config('mail.admin'))->send(
            new NewRefundRequestMail($refundRequest)
        );

        return redirect()->route('orders.show', $order)
            ->with('success', 'Refund request submitted. We will review it shortly.');
    }

    protected function isEligibleForRefund(Order $order): bool
    {
        // Order must be paid
        if ($order->status !== 'paid' && $order->status !== 'completed') {
            return false;
        }

        // Must be within refund window (e.g., 30 days)
        if ($order->paid_at->addDays(30)->isPast()) {
            return false;
        }

        // Must not already have a pending/approved refund
        if ($order->refundRequests()->whereIn('status', ['pending', 'approved'])->exists()) {
            return false;
        }

        return true;
    }
}
```

## Refund Status Values

Xendit uses the following status values for refunds (`RefundStatus` enum):

| Status | Description |
|--------|-------------|
| `PENDING` | Refund is being processed |
| `SUCCEEDED` | Refund completed successfully |
| `FAILED` | Refund failed |
| `CANCELLED` | Refund was cancelled |

## Refund Reasons

`reason` is a required field and must be one of Xendit's fixed enum values (`RefundReason`) — free text is rejected by the API:

| Reason | Description |
|--------|-------------|
| `FRAUDULENT` | Payment was fraudulent |
| `DUPLICATE` | Duplicate payment |
| `REQUESTED_BY_CUSTOMER` | Customer requested the refund |
| `CANCELLATION` | Order/booking was cancelled |
| `OTHERS` | Any other reason not covered above |

## Best Practices

### 1. Always Validate Payment Status

```php
use Laraditz\Xendit\Enums\RefundReason;

// ❌ Bad: Refund without checking payment status
$refund = Xendit::refund()->create([
    'payment_request_id' => $paymentRequestId,
    'reason' => RefundReason::Others->value,
]);

// ✅ Good: Verify payment is eligible for refund
$payment = XenditPayment::where('xendit_id', $paymentRequestId)->firstOrFail();

if (!$payment->isPaid()) {
    throw new \Exception('Payment is not in paid status');
}

if ($payment->refunded_at) {
    throw new \Exception('Payment has already been refunded');
}

$refund = Xendit::refund()->create([
    'payment_request_id' => $paymentRequestId,
    'reason' => RefundReason::RequestedByCustomer->value,
]);
```

### 2. Track Refund Status Locally

```php
// Create local refund record
$order->refunds()->create([
    'xendit_refund_id' => $refund->refund_id,
    'payment_id' => $payment->id,
    'amount' => $refund['amount'],
    'reason' => $refund['reason'],
    'status' => 'pending',
    'initiated_by' => auth()->id(),
]);

// Update when webhook received
Event::listen(RefundSucceeded::class, function($event) {
    $refundData = $event->payload['data'];

    Refund::where('xendit_refund_id', $refundData['id'])
        ->update([
            'status' => 'succeeded',
            'completed_at' => now(),
        ]);
});
```

### 3. Handle Partial Refunds Correctly

```php
use Laraditz\Xendit\Enums\RefundReason;

// Track total refunded amount
$payment = XenditPayment::find($paymentId);
$totalRefunded = $payment->refunds()->sum('amount');
$remainingAmount = $payment->amount - $totalRefunded;

if ($refundAmount > $remainingAmount) {
    throw new \Exception("Cannot refund {$refundAmount}. Only {$remainingAmount} remaining.");
}

// Create refund
$refund = Xendit::refund()->create([
    'payment_request_id' => $payment->xendit_id,
    'amount' => $refundAmount,
    'reason' => RefundReason::RequestedByCustomer->value,
]);

// Check if fully refunded
if (($totalRefunded + $refundAmount) >= $payment->amount) {
    $payment->markAsFullyRefunded();
}
```

### 4. Provide Clear Customer Communication

```php
// Send refund confirmation email
Mail::to($order->customer_email)->send(
    new RefundInitiatedMail($order, [
        'refund_id' => $refund->refund_id,
        'amount' => $refund['amount'],
        'reason' => $refund['reason'],
        'estimated_days' => '5-10 business days',
    ])
);

// Send success notification when webhook received
Event::listen(RefundSucceeded::class, function($event) {
    $refundData = $event->payload['data'];
    $order = Order::where('refund_id', $refundData['id'])->first();

    Mail::to($order->customer_email)->send(
        new RefundCompletedMail($order, [
            'refund_id' => $refundData['id'],
            'amount' => $refundData['amount'],
            'refunded_at' => now(),
        ])
    );
});
```

### 5. Log All Refund Operations

```php
use Illuminate\Support\Facades\Log;

try {
    // $reason must already be a valid RefundReason value (e.g. RefundReason::from($input)->value)
    $refund = Xendit::refund()->create([
        'payment_request_id' => $paymentRequestId,
        'amount' => $amount,
        'reason' => $reason,
    ]);

    Log::info('Refund created successfully', [
        'refund_id' => $refund->refund_id,
        'payment_request_id' => $paymentRequestId,
        'amount' => $amount,
        'initiated_by' => auth()->id(),
    ]);

} catch (\Exception $e) {
    Log::error('Refund creation failed', [
        'payment_request_id' => $paymentRequestId,
        'amount' => $amount,
        'error' => $e->getMessage(),
        'attempted_by' => auth()->id(),
    ]);

    throw $e;
}
```

### 6. Implement Refund Policies

```php
class RefundPolicy
{
    public function canRefund(User $user, Order $order): bool
    {
        // Must be order owner or admin
        if ($order->user_id !== $user->id && !$user->isAdmin()) {
            return false;
        }

        // Must be paid
        if (!$order->isPaid()) {
            return false;
        }

        // Within refund window
        if ($order->paid_at->addDays(30)->isPast()) {
            return false;
        }

        // Not already refunded
        if ($order->isRefunded()) {
            return false;
        }

        return true;
    }
}
```

## Common Errors and Solutions

### Error: "Payment not found"
```php
use Laraditz\Xendit\Enums\RefundReason;

// Solution: Verify the Payment Request ID is correct
$payment = XenditPayment::where('xendit_id', $paymentRequestId)->firstOrFail();
$refund = Xendit::refund()->create([
    'payment_request_id' => $payment->xendit_id, // Use correct Xendit Payment Request ID
    'reason' => RefundReason::Others->value,
]);
```

### Error: "Refund amount exceeds payment amount"
```php
// Solution: Check remaining refundable amount
$totalRefunded = $payment->refunds()->sum('amount');
$maxRefundable = $payment->amount - $totalRefunded;

if ($refundAmount > $maxRefundable) {
    throw new \Exception("Maximum refundable amount is {$maxRefundable}");
}
```

### Error: "Payment method does not support refunds"
```php
// Solution: Check payment method before attempting refund
$payment = XenditPayment::find($paymentId);

if (!in_array($payment->payment_method, ['CARD', 'EWALLET', 'VIRTUAL_ACCOUNT'])) {
    throw new \Exception('This payment method does not support refunds');
}
```

## Related Documentation

- [Payment](payment.md) - Managing payments
- [Payment Request](payment-request.md) - Creating payments
- [Webhooks](webhooks.md) - Handling refund events
