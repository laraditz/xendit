# Payment Link

The Payment Link API allows you to create shareable payment links that can be sent to customers via email, SMS, or messaging apps. Payment links are perfect for invoices, remote payments, or situations where you can't integrate a checkout page.

**Official Documentation:** https://docs.xendit.co/apidocs/payment-link

## Available Methods

### `create(array $data): array`

Create a new payment link that customers can use to make payments.

**Official API:** `POST /payment_links`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `amount` | number | Yes | Payment amount |
| `currency` | string | Yes | Currency code (IDR, PHP, THB, VND, MYR) |
| `description` | string | No | Payment description |
| `customer` | object | No | Customer information |
| `items` | array | No | Line items |
| `success_redirect_url` | string | No | Success redirect URL |
| `failure_redirect_url` | string | No | Failure redirect URL |
| `metadata` | object | No | Custom metadata |
| `expires_at` | string | No | Link expiry date (ISO 8601) |

**Example:**

```php
use Laraditz\Xendit\Facades\Xendit;

$link = Xendit::paymentLink()->create([
    'amount' => 100000,
    'currency' => 'MYR',
    'description' => 'Invoice #INV-001',
    'customer' => [
        'email' => 'customer@example.com',
        'given_names' => 'John Doe',
        'mobile_number' => '+60123456789',
    ],
    'success_redirect_url' => 'https://yourapp.com/success',
    'failure_redirect_url' => 'https://yourapp.com/failed',
    'metadata' => [
        'invoice_id' => 'INV-001',
    ],
]);

// Response structure
[
    'id' => 'pl_12345678',
    'url' => 'https://checkout.xendit.co/web/pl_12345678',
    'amount' => 100000,
    'currency' => 'MYR',
    'description' => 'Invoice #INV-001',
    'status' => 'ACTIVE',
    'created_at' => '2024-01-15T10:00:00Z',
    ...
]

// Share the link with customer
$paymentUrl = $link['url'];
```

### `get(string $id): array`

Get the status and details of a payment link.

**Official API:** `GET /payment_links/{id}`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Payment link ID from Xendit |

**Example:**

```php
$link = Xendit::paymentLink()->get('pl_12345678');

// Check link status
if ($link['status'] === 'PAID') {
    // Payment completed
}
```

## Usage Examples

### Example 1: Simple Invoice Payment Link

```php
use Laraditz\Xendit\Facades\Xendit;

public function generateInvoiceLink($invoiceId)
{
    $invoice = Invoice::findOrFail($invoiceId);

    $link = Xendit::paymentLink()->create([
        'amount' => $invoice->total,
        'currency' => 'MYR',
        'description' => "Invoice #{$invoice->number}",
        'customer' => [
            'email' => $invoice->customer_email,
            'given_names' => $invoice->customer_name,
        ],
        'items' => $invoice->items->map(fn($item) => [
            'name' => $item->description,
            'quantity' => $item->quantity,
            'price' => $item->unit_price,
        ])->toArray(),
        'success_redirect_url' => route('invoice.paid', $invoice),
        'metadata' => [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
        ],
        'expires_at' => $invoice->due_date->toISOString(),
    ]);

    // Store payment link
    $invoice->update([
        'payment_link_id' => $link['id'],
        'payment_link_url' => $link['url'],
    ]);

    return $link['url'];
}
```

### Example 2: Send Payment Link via Email

```php
use Laraditz\Xendit\Facades\Xendit;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentLinkMail;

public function sendPaymentLink($invoiceId)
{
    $invoice = Invoice::findOrFail($invoiceId);

    // Create payment link
    $link = Xendit::paymentLink()->create([
        'amount' => $invoice->total,
        'currency' => 'MYR',
        'description' => "Invoice #{$invoice->number}",
        'customer' => [
            'email' => $invoice->customer_email,
            'given_names' => $invoice->customer_name,
        ],
        'success_redirect_url' => route('invoice.paid', $invoice),
        'metadata' => [
            'invoice_id' => $invoice->id,
        ],
        'expires_at' => now()->addDays(7)->toISOString(),
    ]);

    // Store link
    $invoice->update([
        'payment_link_id' => $link['id'],
        'payment_link_url' => $link['url'],
        'payment_link_sent_at' => now(),
    ]);

    // Send email with payment link
    Mail::to($invoice->customer_email)->send(
        new PaymentLinkMail($invoice, $link['url'])
    );

    return response()->json([
        'success' => true,
        'message' => 'Payment link sent successfully',
    ]);
}
```

### Example 3: WhatsApp Payment Link

```php
use Laraditz\Xendit\Facades\Xendit;

public function sendWhatsAppPaymentLink($orderId)
{
    $order = Order::findOrFail($orderId);

    // Create payment link
    $link = Xendit::paymentLink()->create([
        'amount' => $order->total,
        'currency' => 'MYR',
        'description' => "Order #{$order->number}",
        'customer' => [
            'mobile_number' => $order->customer_phone,
            'given_names' => $order->customer_name,
        ],
        'metadata' => [
            'order_id' => $order->id,
        ],
        'expires_at' => now()->addHours(24)->toISOString(),
    ]);

    // Generate WhatsApp message
    $message = urlencode(
        "Hello {$order->customer_name},\n\n" .
        "Your order #{$order->number} is ready for payment.\n\n" .
        "Amount: " . formatCurrency($order->total) . "\n" .
        "Pay here: {$link['url']}\n\n" .
        "This link expires in 24 hours."
    );

    $whatsappUrl = "https://wa.me/{$order->customer_phone}?text={$message}";

    // Store link
    $order->update([
        'payment_link_id' => $link['id'],
        'payment_link_url' => $link['url'],
    ]);

    return response()->json([
        'success' => true,
        'whatsapp_url' => $whatsappUrl,
        'payment_link' => $link['url'],
    ]);
}
```

### Example 4: Recurring Membership Payment Links

```php
use Laraditz\Xendit\Facades\Xendit;
use App\Models\Membership;

class GenerateMembershipRenewalJob
{
    public function handle()
    {
        // Find memberships expiring in 7 days
        $expiringMemberships = Membership::where('expires_at', now()->addDays(7)->toDateString())
            ->where('auto_renew', false)
            ->get();

        foreach ($expiringMemberships as $membership) {
            $user = $membership->user;

            // Create renewal payment link
            $link = Xendit::paymentLink()->create([
                'amount' => $membership->plan->price,
                'currency' => 'MYR',
                'description' => "Membership Renewal - {$membership->plan->name}",
                'customer' => [
                    'email' => $user->email,
                    'given_names' => $user->name,
                ],
                'success_redirect_url' => route('membership.renewed'),
                'metadata' => [
                    'membership_id' => $membership->id,
                    'user_id' => $user->id,
                    'renewal' => true,
                ],
                'expires_at' => $membership->expires_at->toISOString(),
            ]);

            // Store payment link
            $membership->update([
                'renewal_payment_link_id' => $link['id'],
                'renewal_payment_link_url' => $link['url'],
            ]);

            // Send renewal reminder
            Mail::to($user->email)->send(
                new MembershipRenewalMail($membership, $link['url'])
            );
        }
    }
}
```

### Example 5: Product Purchase via Social Media

```php
use Laraditz\Xendit\Facades\Xendit;

public function createInstagramPaymentLink(Request $request)
{
    $product = Product::findOrFail($request->product_id);

    // Create payment link
    $link = Xendit::paymentLink()->create([
        'amount' => $product->price,
        'currency' => 'MYR',
        'description' => $product->name,
        'items' => [
            [
                'name' => $product->name,
                'quantity' => 1,
                'price' => $product->price,
            ],
        ],
        'success_redirect_url' => route('order.confirmation'),
        'failure_redirect_url' => route('products.show', $product),
        'metadata' => [
            'product_id' => $product->id,
            'source' => 'instagram',
        ],
        'expires_at' => now()->addHours(3)->toISOString(),
    ]);

    // Create pending order
    $order = Order::create([
        'payment_link_id' => $link['id'],
        'product_id' => $product->id,
        'amount' => $product->price,
        'status' => 'pending',
        'source' => 'instagram',
    ]);

    // Return shortened link for Instagram bio
    $shortLink = $this->shortenUrl($link['url']);

    return response()->json([
        'success' => true,
        'payment_link' => $link['url'],
        'short_link' => $shortLink,
        'order_id' => $order->id,
    ]);
}
```

### Example 6: Event Registration with Payment Link

```php
use Laraditz\Xendit\Facades\Xendit;

public function registerForEvent(Request $request, $eventId)
{
    $event = Event::findOrFail($eventId);
    $user = $request->user();

    // Check availability
    if ($event->isFull()) {
        return back()->withError('Event is fully booked');
    }

    // Create registration
    $registration = $event->registrations()->create([
        'user_id' => $user->id,
        'ticket_type' => $request->ticket_type,
        'quantity' => $request->quantity,
        'status' => 'pending',
    ]);

    $amount = $event->getTicketPrice($request->ticket_type) * $request->quantity;

    // Create payment link
    $link = Xendit::paymentLink()->create([
        'amount' => $amount,
        'currency' => 'MYR',
        'description' => "Event Registration - {$event->name}",
        'customer' => [
            'email' => $user->email,
            'given_names' => $user->name,
            'mobile_number' => $user->phone,
        ],
        'items' => [
            [
                'name' => "{$event->name} - {$request->ticket_type} Ticket",
                'quantity' => $request->quantity,
                'price' => $event->getTicketPrice($request->ticket_type),
            ],
        ],
        'success_redirect_url' => route('event.registration.confirmed', $registration),
        'metadata' => [
            'event_id' => $event->id,
            'registration_id' => $registration->id,
        ],
        'expires_at' => now()->addHours(1)->toISOString(),
    ]);

    // Store payment link
    $registration->update([
        'payment_link_id' => $link['id'],
        'payment_link_url' => $link['url'],
    ]);

    // Send email with payment link
    Mail::to($user->email)->send(
        new EventRegistrationMail($registration, $link['url'])
    );

    return redirect()->route('registrations.show', $registration)
        ->with('success', 'Registration created. Please complete payment.');
}
```

### Example 7: Check Payment Link Status

```php
use Laraditz\Xendit\Facades\Xendit;

public function checkPaymentLinkStatus($invoiceId)
{
    $invoice = Invoice::findOrFail($invoiceId);

    if (!$invoice->payment_link_id) {
        return response()->json([
            'success' => false,
            'message' => 'No payment link found',
        ]);
    }

    try {
        // Get current status from Xendit
        $link = Xendit::paymentLink()->get($invoice->payment_link_id);

        // Update local status
        if ($link['status'] === 'PAID' && $invoice->status !== 'paid') {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Send payment confirmation
            Mail::to($invoice->customer_email)->send(
                new PaymentConfirmedMail($invoice)
            );
        }

        return response()->json([
            'success' => true,
            'status' => $link['status'],
            'paid' => $link['status'] === 'PAID',
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
}
```

## Payment Link Status Values

Xendit uses the following status values for payment links:

| Status | Description |
|--------|-------------|
| `ACTIVE` | Link is active and awaiting payment |
| `PAID` | Payment completed successfully |
| `EXPIRED` | Link has expired |
| `INACTIVE` | Link has been deactivated |

## Best Practices

### 1. Set Appropriate Expiry Times

```php
// ✅ Good: Set expiry based on context
// Invoice - 7 days
$link = Xendit::paymentLink()->create([
    'amount' => $invoice->total,
    'expires_at' => now()->addDays(7)->toISOString(),
]);

// Event registration - 1 hour (to prevent overselling)
$link = Xendit::paymentLink()->create([
    'amount' => $ticket->price,
    'expires_at' => now()->addHours(1)->toISOString(),
]);

// Social media flash sale - 3 hours
$link = Xendit::paymentLink()->create([
    'amount' => $product->price,
    'expires_at' => now()->addHours(3)->toISOString(),
]);
```

### 2. Include Clear Descriptions

```php
// ❌ Bad: Vague description
$link = Xendit::paymentLink()->create([
    'amount' => 100000,
    'description' => 'Payment',
]);

// ✅ Good: Clear, specific description
$link = Xendit::paymentLink()->create([
    'amount' => 100000,
    'description' => "Invoice #INV-2024-001 - Website Development Services",
]);
```

### 3. Store Payment Link References

```php
// Always store the payment link ID and URL
$invoice->update([
    'payment_link_id' => $link['id'],
    'payment_link_url' => $link['url'],
    'payment_link_created_at' => now(),
    'payment_link_expires_at' => $link['expires_at'],
]);

// This allows you to:
// 1. Check payment status later
// 2. Resend the link if needed
// 3. Track conversion rates
```

### 4. Handle Webhooks for Payment Links

```php
use Laraditz\Xendit\Events\PaymentPaid;

Event::listen(PaymentPaid::class, function($event) {
    $payment = $event->payment;

    // Find invoice by payment link
    $invoice = Invoice::where('payment_link_id', $payment->external_id)->first();

    if ($invoice) {
        $invoice->markAsPaid();

        // Send confirmation
        Mail::to($invoice->customer_email)->send(
            new PaymentReceivedMail($invoice)
        );
    }
});
```

### 5. Implement Link Regeneration

```php
public function regeneratePaymentLink($invoiceId)
{
    $invoice = Invoice::findOrFail($invoiceId);

    // Can only regenerate for unpaid invoices
    if ($invoice->isPaid()) {
        return back()->withError('Invoice is already paid');
    }

    // Check if current link is expired
    if ($invoice->payment_link_id) {
        $existingLink = Xendit::paymentLink()->get($invoice->payment_link_id);

        if ($existingLink['status'] === 'ACTIVE') {
            return back()->with('info', 'Current payment link is still active');
        }
    }

    // Create new payment link
    $link = Xendit::paymentLink()->create([
        'amount' => $invoice->total,
        'currency' => 'MYR',
        'description' => "Invoice #{$invoice->number}",
        'customer' => [
            'email' => $invoice->customer_email,
        ],
        'expires_at' => now()->addDays(7)->toISOString(),
    ]);

    $invoice->update([
        'payment_link_id' => $link['id'],
        'payment_link_url' => $link['url'],
    ]);

    // Resend email
    Mail::to($invoice->customer_email)->send(
        new PaymentLinkMail($invoice, $link['url'])
    );

    return back()->with('success', 'New payment link generated and sent');
}
```

### 6. Track Payment Link Performance

```php
// Track link metrics
$invoice->paymentLinkMetrics()->create([
    'payment_link_id' => $link['id'],
    'sent_at' => now(),
    'sent_via' => 'email', // email, whatsapp, sms, etc.
]);

// Track clicks (optional - via redirect)
Route::get('/pay/{invoiceId}', function($invoiceId) {
    $invoice = Invoice::findOrFail($invoiceId);

    $invoice->paymentLinkMetrics()->update([
        'clicked_at' => now(),
        'clicks' => DB::raw('clicks + 1'),
    ]);

    return redirect($invoice->payment_link_url);
});

// Analyze conversion rates
$metrics = Invoice::selectRaw('
    COUNT(*) as total_links,
    SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_count,
    AVG(TIMESTAMPDIFF(SECOND, payment_link_created_at, paid_at)) as avg_payment_time
')->get();
```

## Email Template Example

```blade
<!-- resources/views/emails/payment-link.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <style>
        .button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h2>Payment Required</h2>

    <p>Hello {{ $invoice->customer_name }},</p>

    <p>Your invoice #{{ $invoice->number }} is ready for payment.</p>

    <table>
        <tr>
            <td><strong>Amount:</strong></td>
            <td>{{ formatCurrency($invoice->total) }}</td>
        </tr>
        <tr>
            <td><strong>Due Date:</strong></td>
            <td>{{ $invoice->due_date->format('M d, Y') }}</td>
        </tr>
    </table>

    <p>
        <a href="{{ $paymentUrl }}" class="button">
            Pay Now
        </a>
    </p>

    <p>Or copy this link: {{ $paymentUrl }}</p>

    <p>
        <small>
            This payment link will expire on {{ $invoice->payment_link_expires_at->format('M d, Y H:i') }}
        </small>
    </p>

    <p>Thank you for your business!</p>
</body>
</html>
```

## Security Considerations

1. **Validate Ownership**: Ensure users can only access their own payment links
2. **Set Expiry Times**: Always set reasonable expiry times
3. **Use HTTPS**: Payment links automatically use HTTPS
4. **Don't Expose Sensitive Data**: Don't include sensitive information in metadata
5. **Verify Webhooks**: Always verify webhook signatures before processing

## Related Documentation

- [Payment Request](payment-request.md) - Alternative payment method
- [Payment](payment.md) - Managing payments
- [Webhooks](webhooks.md) - Handling payment events
