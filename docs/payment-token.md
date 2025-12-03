# Payment Token

The Payment Token API allows you to save and manage customer payment methods for future use. This enables one-click payments and recurring billing.

**Official Documentation:** https://docs.xendit.co/apidocs/create-payment-token

## Available Methods

### `create(array $data): array`

Create a new payment token to save a customer's payment method.

**Official API:** `POST /payment_tokens`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `customer_id` | string | Yes | Your customer identifier |
| `type` | string | Yes | Token type (CARD, DIRECT_DEBIT, etc.) |
| `reusability` | string | Yes | MULTIPLE_USE or ONE_TIME_USE |
| `card` | object | Conditional | Card details (required if type is CARD) |
| `direct_debit` | object | Conditional | Direct debit details |
| `metadata` | object | No | Custom metadata |

**Example:**

```php
use Laraditz\Xendit\Facades\Xendit;

// Create a card token
$token = Xendit::paymentToken()->create([
    'customer_id' => 'customer-123',
    'type' => 'CARD',
    'reusability' => 'MULTIPLE_USE',
    'card' => [
        'number' => '4000000000000002',
        'exp_month' => '12',
        'exp_year' => '2025',
        'cvv' => '123',
        'cardholder_name' => 'John Doe',
    ],
    'metadata' => [
        'user_id' => 123,
    ],
]);

// Response structure
[
    'id' => 'pt_12345678',
    'customer_id' => 'customer-123',
    'type' => 'CARD',
    'status' => 'ACTIVE',
    'reusability' => 'MULTIPLE_USE',
    'card' => [
        'last4' => '0002',
        'brand' => 'VISA',
        'exp_month' => '12',
        'exp_year' => '2025',
    ],
    'created_at' => '2024-01-15T10:00:00Z',
    ...
]
```

### `get(string $id): array`

Get the status and details of a payment token.

**Official API:** `GET /payment_tokens/{id}`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Payment token ID from Xendit |

**Example:**

```php
$token = Xendit::paymentToken()->get('pt_12345678');

// Check token status
if ($token['status'] === 'ACTIVE') {
    // Token is ready to use
}
```

### `cancel(string $id): array`

Deactivate a payment token so it can no longer be used.

**Official API:** `POST /payment_tokens/{id}/deactivate`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Payment token ID from Xendit |

**Example:**

```php
$deactivated = Xendit::paymentToken()->cancel('pt_12345678');

// Response
[
    'id' => 'pt_12345678',
    'status' => 'INACTIVE',
    ...
]
```

## Usage Examples

### Example 1: Save Credit Card for Future Payments

```php
use Laraditz\Xendit\Facades\Xendit;

// When customer wants to save their card
$token = Xendit::paymentToken()->create([
    'customer_id' => "user-{$user->id}",
    'type' => 'CARD',
    'reusability' => 'MULTIPLE_USE',
    'card' => [
        'number' => $request->card_number,
        'exp_month' => $request->exp_month,
        'exp_year' => $request->exp_year,
        'cvv' => $request->cvv,
        'cardholder_name' => $request->cardholder_name,
    ],
    'metadata' => [
        'user_id' => $user->id,
        'saved_at' => now()->toISOString(),
    ],
]);

// Store token reference in your database
$user->savedPaymentMethods()->create([
    'token_id' => $token['id'],
    'type' => 'card',
    'last4' => $token['card']['last4'],
    'brand' => $token['card']['brand'],
    'exp_month' => $token['card']['exp_month'],
    'exp_year' => $token['card']['exp_year'],
]);
```

### Example 2: Use Saved Payment Token

```php
use Laraditz\Xendit\Facades\Xendit;

// Get saved payment method
$savedMethod = $user->savedPaymentMethods()->find($paymentMethodId);

// Verify token is still active
$tokenStatus = Xendit::paymentToken()->get($savedMethod->token_id);

if ($tokenStatus['status'] !== 'ACTIVE') {
    return back()->withError('Payment method is no longer valid');
}

// Create payment using saved token
$payment = Xendit::paymentRequest()
    ->amount($order->total)
    ->email($user->email)
    ->paymentMethods([
        [
            'type' => 'CARD',
            'reusability' => 'MULTIPLE_USE',
            'payment_token_id' => $savedMethod->token_id,
        ],
    ])
    ->for($order)
    ->create();

// Process payment
return redirect($payment->payment_url);
```

### Example 3: List User's Saved Payment Methods

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laraditz\Xendit\Facades\Xendit;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get saved payment methods from database
        $savedMethods = $user->savedPaymentMethods;

        // Verify each token is still active
        $activeMethods = $savedMethods->filter(function ($method) {
            try {
                $token = Xendit::paymentToken()->get($method->token_id);
                return $token['status'] === 'ACTIVE';
            } catch (\Exception $e) {
                // Token no longer exists
                $method->delete();
                return false;
            }
        });

        return view('payment-methods.index', [
            'paymentMethods' => $activeMethods,
        ]);
    }
}
```

### Example 4: Remove Saved Payment Method

```php
use Laraditz\Xendit\Facades\Xendit;

public function destroy(Request $request, $paymentMethodId)
{
    $user = $request->user();
    $savedMethod = $user->savedPaymentMethods()->findOrFail($paymentMethodId);

    try {
        // Deactivate token at Xendit
        Xendit::paymentToken()->cancel($savedMethod->token_id);

        // Remove from database
        $savedMethod->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment method removed successfully',
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
}
```

### Example 5: Direct Debit Token

```php
use Laraditz\Xendit\Facades\Xendit;

// Create direct debit token
$token = Xendit::paymentToken()->create([
    'customer_id' => "user-{$user->id}",
    'type' => 'DIRECT_DEBIT',
    'reusability' => 'MULTIPLE_USE',
    'direct_debit' => [
        'channel_code' => 'BCA_KLIKPAY',
        'channel_properties' => [
            'success_redirect_url' => 'https://yourapp.com/success',
            'failure_redirect_url' => 'https://yourapp.com/failed',
        ],
    ],
    'metadata' => [
        'user_id' => $user->id,
    ],
]);

// Redirect user to authorize direct debit
return redirect($token['direct_debit']['authorization_url']);
```

### Example 6: Recurring Subscription Payments

```php
use Laraditz\Xendit\Facades\Xendit;
use App\Models\Subscription;

class SubscriptionBillingJob
{
    public function handle(Subscription $subscription)
    {
        $user = $subscription->user;

        // Get saved payment token
        $paymentMethod = $user->defaultPaymentMethod;

        if (!$paymentMethod) {
            throw new \Exception('No payment method available');
        }

        // Verify token is active
        $tokenStatus = Xendit::paymentToken()->get($paymentMethod->token_id);

        if ($tokenStatus['status'] !== 'ACTIVE') {
            $subscription->suspend('Payment method inactive');
            return;
        }

        // Create payment for this billing cycle
        $payment = Xendit::paymentRequest()
            ->amount($subscription->plan->price)
            ->description("Subscription billing - {$subscription->plan->name}")
            ->email($user->email)
            ->paymentMethods([
                [
                    'type' => 'CARD',
                    'reusability' => 'MULTIPLE_USE',
                    'payment_token_id' => $paymentMethod->token_id,
                ],
            ])
            ->metadata([
                'subscription_id' => $subscription->id,
                'billing_period' => now()->format('Y-m'),
            ])
            ->for($subscription)
            ->create();

        // Log payment attempt
        $subscription->payments()->create([
            'xendit_payment_id' => $payment->id,
            'amount' => $subscription->plan->price,
            'status' => 'pending',
        ]);
    }
}
```

## Payment Token Status Values

Xendit uses the following status values for payment tokens:

| Status | Description |
|--------|-------------|
| `ACTIVE` | Token is active and can be used |
| `INACTIVE` | Token has been deactivated |
| `EXPIRED` | Token has expired |
| `PENDING` | Token is being verified |
| `FAILED` | Token verification failed |

## Token Types

| Type | Description |
|------|-------------|
| `CARD` | Credit/debit card token |
| `DIRECT_DEBIT` | Bank account direct debit token |
| `EWALLET` | E-wallet account token |

## Best Practices

### 1. Always Verify Token Status Before Use

```php
// ❌ Bad: Use token without checking
$payment = Xendit::paymentRequest()
    ->paymentMethods([
        ['payment_token_id' => $tokenId],
    ])
    ->create();

// ✅ Good: Verify token first
$token = Xendit::paymentToken()->get($tokenId);

if ($token['status'] !== 'ACTIVE') {
    throw new \Exception('Payment method is no longer valid');
}

$payment = Xendit::paymentRequest()
    ->paymentMethods([
        ['payment_token_id' => $tokenId],
    ])
    ->create();
```

### 2. Handle PCI Compliance for Cards

```php
// ❌ Bad: Store card details in your database
$user->update([
    'card_number' => $request->card_number,
    'cvv' => $request->cvv,
]);

// ✅ Good: Only store token reference
$token = Xendit::paymentToken()->create([
    'customer_id' => "user-{$user->id}",
    'type' => 'CARD',
    'card' => [...],
]);

$user->savedPaymentMethods()->create([
    'token_id' => $token['id'],
    'last4' => $token['card']['last4'],
    'brand' => $token['card']['brand'],
    // Never store full card details
]);
```

### 3. Implement Webhook Listeners

```php
// Listen for token events
use Laraditz\Xendit\Events\PaymentTokenCreated;
use Laraditz\Xendit\Events\PaymentTokenActivated;

Event::listen(PaymentTokenCreated::class, function($event) {
    Log::info('Payment token created', $event->payload);
});

Event::listen(PaymentTokenActivated::class, function($event) {
    $tokenData = $event->payload;

    // Update local database
    SavedPaymentMethod::where('token_id', $tokenData['id'])
        ->update(['status' => 'active']);
});
```

### 4. Clean Up Inactive Tokens

```php
use Laraditz\Xendit\Facades\Xendit;

// Scheduled job to clean up inactive tokens
class CleanupInactiveTokensJob
{
    public function handle()
    {
        SavedPaymentMethod::chunk(100, function ($methods) {
            foreach ($methods as $method) {
                try {
                    $token = Xendit::paymentToken()->get($method->token_id);

                    if (in_array($token['status'], ['INACTIVE', 'EXPIRED', 'FAILED'])) {
                        $method->delete();
                    }
                } catch (\Exception $e) {
                    // Token no longer exists
                    $method->delete();
                }
            }
        });
    }
}
```

### 5. Provide Clear User Interface

```php
// Display saved payment methods clearly
@foreach($paymentMethods as $method)
    <div class="payment-method">
        <img src="/images/{{ $method->brand }}.png" alt="{{ $method->brand }}">
        <span>•••• {{ $method->last4 }}</span>
        <span>Expires {{ $method->exp_month }}/{{ $method->exp_year }}</span>
        <button wire:click="remove({{ $method->id }})">Remove</button>
    </div>
@endforeach
```

## Security Considerations

1. **Never Store Sensitive Data**: Only store token IDs, last 4 digits, and metadata
2. **PCI Compliance**: Use Xendit's tokenization to avoid PCI compliance requirements
3. **Verify Token Ownership**: Always verify the token belongs to the authenticated user
4. **Implement Rate Limiting**: Limit token creation attempts to prevent abuse
5. **Use HTTPS Only**: Never transmit card data over insecure connections

## Related Documentation

- [Payment Request](payment-request.md) - Using tokens in payments
- [Webhooks](webhooks.md) - Handling token events
- [Session](session.md) - Payment sessions with saved methods
