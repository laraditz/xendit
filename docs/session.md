# Session

The Session API creates a hosted payment page (Payment Link) or embeddable component (Components SDK) where customers can complete payment. Sessions are persisted to the `xendit_sessions` database table and expose a fluent builder API.

**Official Documentation:** https://docs.xendit.co/apidocs/create-session

## Model

`Xendit::session()` returns a `SessionBuilder`. After `create()`, you get back an `XenditSession` Eloquent model.

### Key Columns

| Column | Type | Description |
|--------|------|-------------|
| `reference_id` | string | Your unique identifier (auto-generated if not set) |
| `payment_session_id` | string | Xendit's session ID |
| `session_type` | enum | `PAY`, `SAVE`, or `SUBSCRIPTION` |
| `mode` | enum | `PAYMENT_LINK` or `COMPONENTS` |
| `status` | enum | `Active`, `Completed`, `Expired`, `Canceled` |
| `amount` | decimal | Session amount |
| `currency` | char(3) | Currency code (defaults to config `xendit.default_currency`) |
| `payment_link_url` | text | Redirect URL for `PAYMENT_LINK` mode |
| `components_sdk_key` | text | SDK key for `COMPONENTS` mode |
| `expires_at` | datetime | Session expiry |
| `completed_at` | datetime | Set when session completes |
| `canceled_at` | datetime | Set when session is canceled |

### Status Enum

```php
use Laraditz\Xendit\Enums\SessionStatus;

SessionStatus::Active;    // 1 — session is open
SessionStatus::Completed; // 2 — payment collected
SessionStatus::Expired;   // 3 — session timed out
SessionStatus::Canceled;  // 4 — canceled via API

$session->status->isActive(); // true if Active
$session->status->isFinal();  // true if Completed, Expired, or Canceled
$session->status->label();    // 'Active', 'Completed', etc.
```

## Fluent Builder Methods

| Method | Description |
|--------|-------------|
| `referenceId(string)` | Your unique reference (auto-generated if omitted) |
| `amount(float)` | Payment amount |
| `currency(string)` | Currency code |
| `country(string)` | Two-letter country code |
| `sessionType(string\|SessionType)` | `PAY`, `SAVE`, or `SUBSCRIPTION` |
| `mode(string\|SessionMode)` | `PAYMENT_LINK` or `COMPONENTS` |
| `successUrl(string)` | Redirect on payment success |
| `cancelUrl(string)` | Redirect on payment cancel |
| `customer(array)` | Inline customer object |
| `customerId(string)` | Xendit customer ID |
| `captureMethod(string)` | `AUTOMATIC` (default for PAY) or `MANUAL` |
| `allowSavePaymentMethod(string)` | Allow saving payment method |
| `allowedPaymentChannels(array)` | Restrict available channels |
| `items(array)` | Line items |
| `locale(string)` | UI locale |
| `expiresAt(Carbon)` | Custom expiry datetime |
| `metadata(array)` | Custom key-value metadata |
| `for(Model)` | Attach session to any Eloquent model (polymorphic) |

## `create(): XenditSession`

Creates the session record in your database, calls the Xendit API, and updates the record with the API response. If the API call fails, the database record is force-deleted.

```php
use Laraditz\Xendit\Facades\Xendit;

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

// Redirect the user
return redirect($session->payment_link_url);
```

### Auto-generated reference ID

If you omit `referenceId()`, one is generated with the prefix `XND-SESSION-`:

```php
$session = Xendit::session()
    ->amount(50.00)
    ->sessionType('PAY')
    ->mode('PAYMENT_LINK')
    ->create();

echo $session->reference_id; // "XND-SESSION-ABCDEF123456"
```

### SAVE session (no capture_method)

For `SAVE` and `SUBSCRIPTION` session types, `capture_method` is intentionally omitted:

```php
$session = Xendit::session()
    ->referenceId('save-card-001')
    ->amount(0)
    ->sessionType('SAVE')
    ->mode('PAYMENT_LINK')
    ->create();
```

### Inline customer

```php
$session = Xendit::session()
    ->referenceId('order-002')
    ->amount(200.00)
    ->sessionType('PAY')
    ->mode('PAYMENT_LINK')
    ->customer([
        'type'  => 'INDIVIDUAL',
        'email' => 'john@example.com',
        'given_names' => 'John',
    ])
    ->create();
```

### Attach to a model (polymorphic)

```php
$order = Order::find(1);

$session = Xendit::session()
    ->amount($order->total)
    ->sessionType('PAY')
    ->mode('PAYMENT_LINK')
    ->for($order)
    ->create();

// Retrieve later
$session->payable; // returns the Order model
```

### Custom expiry

```php
use Carbon\Carbon;

$session = Xendit::session()
    ->referenceId('order-003')
    ->amount(150.00)
    ->sessionType('PAY')
    ->mode('PAYMENT_LINK')
    ->expiresAt(Carbon::now()->addHours(2))
    ->create();
```

## `get(string $id): array`

Fetch live session data from the Xendit API.

```php
$data = Xendit::session()->get('ps-abc123');

if ($data['status'] === 'COMPLETED') {
    // Payment collected
}
```

## `cancel(string $id): array`

Cancel an active session. Updates the local `XenditSession` record to `Canceled` and dispatches a `SessionCanceled` event.

```php
Xendit::session()->cancel('ps-abc123');

// The local record is updated automatically
$session = XenditSession::paymentSessionId('ps-abc123')->first();
echo $session->status->label(); // "Canceled"
```

## Model Methods

```php
use Laraditz\Xendit\Models\XenditSession;

$session = XenditSession::referenceId('order-001')->first();

// Status transitions
$session->markAsCompleted(); // sets status=Completed, completed_at=now()
$session->markAsExpired();   // sets status=Expired
$session->markAsCanceled();  // sets status=Canceled, canceled_at=now()

// Relationships
$session->xenditCustomer; // XenditCustomer via customer_id → xendit_id
$session->payable;        // Polymorphic: your Order, User, etc.

// Scopes
XenditSession::referenceId('order-001')->first();
XenditSession::paymentSessionId('ps-abc123')->first();
```

## Events

| Event | Dispatched When |
|-------|----------------|
| `SessionCreated` | After `create()` succeeds |
| `SessionCompleted` | Webhook `payment_session.completed` received |
| `SessionExpired` | Webhook `payment_session.expired` received |
| `SessionCanceled` | After `cancel()` is called |

All four events expose `$event->session` (an `XenditSession` model instance).

### Listening to session events

```php
use Laraditz\Xendit\Events\SessionCompleted;
use Laraditz\Xendit\Events\SessionExpired;
use Laraditz\Xendit\Events\SessionCanceled;

// In EventServiceProvider
protected $listen = [
    SessionCompleted::class => [FulfillOrder::class],
    SessionExpired::class   => [CancelPendingOrder::class],
    SessionCanceled::class  => [ReleaseReservedStock::class],
];
```

### Example listener

```php
namespace App\Listeners;

use Laraditz\Xendit\Events\SessionCompleted;

class FulfillOrder
{
    public function handle(SessionCompleted $event): void
    {
        $session = $event->session;
        $order   = $session->payable; // your Order model

        if (!$order) {
            return;
        }

        $order->update(['status' => 'confirmed', 'paid_at' => now()]);
    }
}
```

## Webhook Handling

The package routes `payment_session.completed` and `payment_session.expired` Xendit webhooks automatically — no extra configuration needed. The handler looks up the session by `reference_id` from the webhook payload and calls `markAsCompleted()` or `markAsExpired()` respectively, then dispatches the corresponding event.

See [Webhooks](webhooks.md) for full webhook setup instructions.

## Session Types

| Type | Description |
|------|-------------|
| `PAY` | Standard payment collection |
| `SAVE` | Save a payment method without charging |
| `SUBSCRIPTION` | Set up recurring payments |

## Modes

| Mode | Description |
|------|-------------|
| `PAYMENT_LINK` | Hosted page — redirect via `payment_link_url` |
| `COMPONENTS` | Embeddable — use `components_sdk_key` with Xendit JS SDK |

## Related Documentation

- [Customer](customer.md) - Managing Xendit customers
- [Webhooks](webhooks.md) - Handling session events
- [Payment Request](payment-request.md) - Alternative single-payment flow
