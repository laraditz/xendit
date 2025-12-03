# Session

The Session API provides a way to create secure payment sessions that allow customers to save payment methods and complete payments within a single flow. Sessions are particularly useful for checkout processes where you want to offer saved payment methods.

**Official Documentation:** https://docs.xendit.co/apidocs/create-session

## Available Methods

### `create(array $data): array`

Create a new payment session.

**Official API:** `POST /sessions`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `amount` | number | Yes | Session amount |
| `currency` | string | Yes | Currency code (IDR, PHP, THB, VND, MYR) |
| `customer_id` | string | No | Customer identifier |
| `success_return_url` | string | Yes | Success redirect URL |
| `failure_return_url` | string | Yes | Failure redirect URL |
| `payment_methods` | array | No | Allowed payment methods |
| `metadata` | object | No | Custom metadata |
| `description` | string | No | Session description |

**Example:**

```php
use Laraditz\Xendit\Facades\Xendit;

$session = Xendit::session()->create([
    'amount' => 100000,
    'currency' => 'MYR',
    'customer_id' => 'customer-123',
    'success_return_url' => 'https://yourapp.com/success',
    'failure_return_url' => 'https://yourapp.com/failed',
    'payment_methods' => [
        'CARD',
        'EWALLET',
        'DIRECT_DEBIT',
    ],
    'metadata' => [
        'order_id' => 123,
    ],
]);

// Response structure
[
    'id' => 'session_12345678',
    'amount' => 100000,
    'currency' => 'MYR',
    'status' => 'ACTIVE',
    'session_url' => 'https://checkout.xendit.co/session/...',
    'success_return_url' => 'https://yourapp.com/success',
    'failure_return_url' => 'https://yourapp.com/failed',
    'created_at' => '2024-01-15T10:00:00Z',
    ...
]
```

### `get(string $id): array`

Get the status and details of a session.

**Official API:** `GET /sessions/{id}`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Session ID from Xendit |

**Example:**

```php
$session = Xendit::session()->get('session_12345678');

// Check session status
if ($session['status'] === 'COMPLETED') {
    // Session completed successfully
}
```

### `cancel(string $id): array`

Cancel an active session.

**Official API:** `POST /sessions/{id}/cancel`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Session ID from Xendit |

**Example:**

```php
$cancelled = Xendit::session()->cancel('session_12345678');

// Response
[
    'id' => 'session_12345678',
    'status' => 'CANCELLED',
    ...
]
```

## Usage Examples

### Example 1: Basic Checkout Session

```php
use Laraditz\Xendit\Facades\Xendit;

public function checkout(Request $request)
{
    $cart = $request->user()->cart;

    // Create a checkout session
    $session = Xendit::session()->create([
        'amount' => $cart->total,
        'currency' => 'MYR',
        'customer_id' => "user-{$request->user()->id}",
        'success_return_url' => route('checkout.success'),
        'failure_return_url' => route('checkout.failed'),
        'description' => 'Order checkout',
        'metadata' => [
            'cart_id' => $cart->id,
            'user_id' => $request->user()->id,
        ],
    ]);

    // Store session ID for tracking
    $cart->update([
        'session_id' => $session['id'],
    ]);

    // Redirect to Xendit checkout
    return redirect($session['session_url']);
}
```

### Example 2: Session with Saved Payment Methods

```php
use Laraditz\Xendit\Facades\Xendit;

// Create session that allows saving payment methods
$session = Xendit::session()->create([
    'amount' => 150000,
    'currency' => 'MYR',
    'customer_id' => "user-{$user->id}",
    'success_return_url' => 'https://yourapp.com/success',
    'failure_return_url' => 'https://yourapp.com/failed',
    'payment_methods' => ['CARD', 'EWALLET'],
    'reusability' => 'MULTIPLE_USE', // Allow saving payment methods
    'metadata' => [
        'save_payment_method' => true,
    ],
]);

return redirect($session['session_url']);
```

### Example 3: Subscription Setup Session

```php
use Laraditz\Xendit\Facades\Xendit;
use App\Models\Subscription;

public function createSubscription(Request $request, $planId)
{
    $user = $request->user();
    $plan = Plan::findOrFail($planId);

    // Create subscription record
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    // Create session for initial payment and token setup
    $session = Xendit::session()->create([
        'amount' => $plan->price,
        'currency' => 'MYR',
        'customer_id' => "user-{$user->id}",
        'success_return_url' => route('subscription.activated', $subscription),
        'failure_return_url' => route('subscription.failed', $subscription),
        'payment_methods' => ['CARD'], // Only cards for subscriptions
        'reusability' => 'MULTIPLE_USE', // Save card for future billing
        'description' => "Subscription: {$plan->name}",
        'metadata' => [
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'setup_intent' => true,
        ],
    ]);

    // Store session reference
    $subscription->update([
        'session_id' => $session['id'],
    ]);

    return redirect($session['session_url']);
}
```

### Example 4: Handle Session Success

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laraditz\Xendit\Facades\Xendit;

class CheckoutController extends Controller
{
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('home')
                ->withError('Invalid session');
        }

        // Verify session status
        $session = Xendit::session()->get($sessionId);

        if ($session['status'] !== 'COMPLETED') {
            return redirect()->route('checkout.failed')
                ->withError('Payment was not completed');
        }

        // Find related order
        $cart = Cart::where('session_id', $sessionId)->firstOrFail();

        // Create order
        $order = Order::create([
            'user_id' => $cart->user_id,
            'total' => $cart->total,
            'status' => 'confirmed',
            'payment_method' => $session['payment_method']['type'] ?? null,
        ]);

        // Transfer cart items to order
        $cart->items->each(function ($item) use ($order) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ]);
        });

        // Clear cart
        $cart->delete();

        return view('checkout.success', compact('order'));
    }

    public function failed(Request $request)
    {
        $sessionId = $request->query('session_id');

        if ($sessionId) {
            $session = Xendit::session()->get($sessionId);

            return view('checkout.failed', [
                'error' => $session['failure_reason'] ?? 'Payment failed',
            ]);
        }

        return view('checkout.failed');
    }
}
```

### Example 5: Cancel Session (Timeout)

```php
use Laraditz\Xendit\Facades\Xendit;
use App\Models\Cart;

// Scheduled job to cancel abandoned sessions
class CancelAbandonedSessionsJob
{
    public function handle()
    {
        // Find carts with active sessions older than 30 minutes
        $abandonedCarts = Cart::whereNotNull('session_id')
            ->where('created_at', '<', now()->subMinutes(30))
            ->get();

        foreach ($abandonedCarts as $cart) {
            try {
                // Get session status
                $session = Xendit::session()->get($cart->session_id);

                // Cancel if still active
                if ($session['status'] === 'ACTIVE') {
                    Xendit::session()->cancel($cart->session_id);

                    Log::info('Cancelled abandoned session', [
                        'session_id' => $cart->session_id,
                        'cart_id' => $cart->id,
                    ]);
                }

                // Clear session from cart
                $cart->update(['session_id' => null]);

            } catch (\Exception $e) {
                Log::error('Failed to cancel session: ' . $e->getMessage());
            }
        }
    }
}
```

### Example 6: Session with Specific Payment Methods

```php
use Laraditz\Xendit\Facades\Xendit;

// Create session with only e-wallets
$session = Xendit::session()->create([
    'amount' => 50000,
    'currency' => 'MYR',
    'customer_id' => "user-{$user->id}",
    'success_return_url' => 'https://yourapp.com/success',
    'failure_return_url' => 'https://yourapp.com/failed',
    'payment_methods' => [
        [
            'type' => 'EWALLET',
            'ewallet' => [
                'channel_code' => 'SHOPEEPAY',
            ],
        ],
        [
            'type' => 'EWALLET',
            'ewallet' => [
                'channel_code' => 'DANA',
            ],
        ],
    ],
]);

// Or use payment method types only
$session = Xendit::session()->create([
    'amount' => 50000,
    'currency' => 'MYR',
    'success_return_url' => 'https://yourapp.com/success',
    'failure_return_url' => 'https://yourapp.com/failed',
    'payment_methods' => ['EWALLET', 'VIRTUAL_ACCOUNT'],
]);
```

### Example 7: Express Checkout Session

```php
use Laraditz\Xendit\Facades\Xendit;

public function expressCheckout(Request $request, $productId)
{
    $product = Product::findOrFail($productId);
    $user = $request->user();

    // Create session directly without cart
    $session = Xendit::session()->create([
        'amount' => $product->price,
        'currency' => 'MYR',
        'customer_id' => "user-{$user->id}",
        'success_return_url' => route('order.confirmed'),
        'failure_return_url' => route('product.show', $product),
        'description' => "Express checkout: {$product->name}",
        'metadata' => [
            'product_id' => $product->id,
            'quantity' => 1,
            'express_checkout' => true,
        ],
    ]);

    // Create pending order
    $order = Order::create([
        'user_id' => $user->id,
        'session_id' => $session['id'],
        'status' => 'pending',
        'total' => $product->price,
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => $product->price,
    ]);

    return redirect($session['session_url']);
}
```

## Session Status Values

Xendit uses the following status values for sessions:

| Status | Description |
|--------|-------------|
| `ACTIVE` | Session is active and ready for payment |
| `COMPLETED` | Payment completed successfully |
| `CANCELLED` | Session was cancelled |
| `EXPIRED` | Session has expired |
| `FAILED` | Payment attempt failed |

## Best Practices

### 1. Handle Return URLs Properly

```php
// ✅ Good: Include session ID in return URL
Route::get('/checkout/success', [CheckoutController::class, 'success'])
    ->name('checkout.success');

public function success(Request $request)
{
    $sessionId = $request->query('session_id');

    // Xendit automatically appends session_id to return URL
    $session = Xendit::session()->get($sessionId);

    // Verify session is actually completed
    if ($session['status'] !== 'COMPLETED') {
        return redirect()->route('checkout.failed');
    }

    // Process order...
}
```

### 2. Set Appropriate Session Expiry

```php
// Create session with custom expiry
$session = Xendit::session()->create([
    'amount' => 100000,
    'currency' => 'MYR',
    'success_return_url' => route('checkout.success'),
    'failure_return_url' => route('checkout.failed'),
    'expires_at' => now()->addHours(2)->toISOString(), // 2 hour expiry
]);
```

### 3. Store Session Reference

```php
// ✅ Good: Store session ID for tracking
$order->update([
    'session_id' => $session['id'],
    'session_created_at' => now(),
]);

// Later, verify payment
$session = Xendit::session()->get($order->session_id);
if ($session['status'] === 'COMPLETED') {
    $order->markAsPaid();
}
```

### 4. Clean Up Expired Sessions

```php
use Laraditz\Xendit\Facades\Xendit;

// Scheduled job
class CleanupExpiredSessionsJob
{
    public function handle()
    {
        Order::whereNotNull('session_id')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->chunk(100, function ($orders) {
                foreach ($orders as $order) {
                    try {
                        $session = Xendit::session()->get($order->session_id);

                        if (in_array($session['status'], ['EXPIRED', 'CANCELLED'])) {
                            $order->update(['status' => 'cancelled']);
                        }
                    } catch (\Exception $e) {
                        // Session not found, mark as expired
                        $order->update(['status' => 'cancelled']);
                    }
                }
            });
    }
}
```

### 5. Prevent Multiple Sessions

```php
// ❌ Bad: Allow multiple active sessions
$session = Xendit::session()->create([...]);

// ✅ Good: Cancel previous session before creating new one
if ($cart->session_id) {
    try {
        $existingSession = Xendit::session()->get($cart->session_id);

        if ($existingSession['status'] === 'ACTIVE') {
            Xendit::session()->cancel($cart->session_id);
        }
    } catch (\Exception $e) {
        // Session doesn't exist, continue
    }
}

$session = Xendit::session()->create([...]);
$cart->update(['session_id' => $session['id']]);
```

### 6. Provide Clear User Feedback

```blade
<!-- checkout.blade.php -->
<div class="checkout-container">
    <h2>Complete Your Purchase</h2>
    <p>You will be redirected to our secure payment partner, Xendit.</p>

    <div class="order-summary">
        <h3>Order Summary</h3>
        <p>Total: {{ formatCurrency($cart->total) }}</p>
    </div>

    <form action="{{ route('checkout.initiate') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary">
            Proceed to Payment
        </button>
    </form>

    <p class="text-muted">
        <small>
            Secure payment powered by Xendit.<br>
            Your payment information is encrypted and secure.
        </small>
    </p>
</div>
```

## Security Considerations

1. **Validate Return URLs**: Always verify session status on return, don't trust URL parameters alone
2. **Use HTTPS**: Ensure all return URLs use HTTPS protocol
3. **Implement CSRF Protection**: Use Laravel's CSRF protection on all forms
4. **Verify Session Ownership**: Check that the session belongs to the authenticated user
5. **Set Reasonable Expiry**: Don't create sessions that last too long

## Related Documentation

- [Payment Request](payment-request.md) - Alternative payment method
- [Payment Token](payment-token.md) - Saving payment methods
- [Payment](payment.md) - Managing payments
- [Webhooks](webhooks.md) - Handling session events
