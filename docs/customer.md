# Customer

The Customer API creates and manages Xendit customers. Customers are persisted to the `xendit_customers` database table and expose a fluent builder API.

**Official Documentation:** https://docs.xendit.co/apidocs/create-customer

## Model

`Xendit::customer()` returns a `CustomerBuilder`. After `create()`, you get back an `XenditCustomer` Eloquent model.

### Key Columns

| Column | Type | Description |
|--------|------|-------------|
| `reference_id` | string | Your unique identifier (required) |
| `xendit_id` | string | Xendit's customer ID |
| `type` | enum | `INDIVIDUAL` (default) or `BUSINESS` |
| `email` | string | Customer email |
| `mobile_number` | string | Mobile number with country code |
| `phone_number` | string | Phone number |
| `individual_detail` | JSON | Given names, surname, DOB, nationality, gender |
| `business_detail` | JSON | Business name, trading name |
| `addresses` | JSON | Array of address objects |
| `kyc_documents` | JSON | KYC documents from Xendit response |
| `identity_accounts` | JSON | Identity accounts from Xendit response |
| `metadata` | JSON | Custom key-value metadata |
| `customer_details` | JSON | Full API response from Xendit |

### Type Enum

```php
use Laraditz\Xendit\Enums\CustomerType;

CustomerType::Individual; // 'INDIVIDUAL' (default)
CustomerType::Business;   // 'BUSINESS'

$customer->type->label(); // 'Individual' or 'Business'
```

## Fluent Builder Methods

| Method | Description |
|--------|-------------|
| `referenceId(string)` | Your unique reference (**required** for `create()`) |
| `type(string\|CustomerType)` | `INDIVIDUAL` or `BUSINESS` (default: `INDIVIDUAL`) |
| `givenNames(string)` | First/given names (individual) |
| `surname(string)` | Surname (individual) |
| `dateOfBirth(string)` | Date of birth `YYYY-MM-DD` (individual) |
| `nationality(string)` | ISO 3166-1 alpha-2 country code (individual) |
| `gender(string)` | `MALE` or `FEMALE` (individual) |
| `businessName(string)` | Registered business name |
| `tradingName(string)` | Trading / DBA name |
| `email(string)` | Customer email address |
| `mobileNumber(string)` | Mobile number with country code (e.g. `+60123456789`) |
| `phoneNumber(string)` | Phone number |
| `address(array)` | Append one address to the addresses list |
| `metadata(array)` | Custom key-value metadata |
| `withHeader(string, string)` | Set a single arbitrary request header |
| `withHeaders(array)` | Merge multiple request headers at once |

## `create(): XenditCustomer`

Creates the customer record in your database, calls the Xendit API, and updates the record with the API response. `referenceId()` is required — an `InvalidArgumentException` is thrown if omitted. If the API call fails, the database record is force-deleted.

### Individual customer

```php
use Laraditz\Xendit\Facades\Xendit;

$customer = Xendit::customer()
    ->referenceId('user-001')
    ->givenNames('John')
    ->surname('Doe')
    ->email('john@example.com')
    ->mobileNumber('+60123456789')
    ->create();

echo $customer->xendit_id;     // Xendit's ID, e.g. "cust_abc123"
echo $customer->reference_id;  // "user-001"
echo $customer->type->label(); // "Individual"
```

### Business customer

```php
$customer = Xendit::customer()
    ->referenceId('biz-001')
    ->type('BUSINESS')
    ->businessName('Acme Sdn Bhd')
    ->tradingName('Acme')
    ->email('billing@acme.com')
    ->phoneNumber('+60312345678')
    ->create();
```

### With address

```php
$customer = Xendit::customer()
    ->referenceId('user-002')
    ->givenNames('Jane')
    ->email('jane@example.com')
    ->address([
        'country'       => 'MY',
        'street_line1'  => '1 Jalan Example',
        'city'          => 'Kuala Lumpur',
        'postal_code'   => '50450',
        'category'      => 'HOME',
        'is_primary'    => true,
    ])
    ->create();
```

### With metadata

```php
$customer = Xendit::customer()
    ->referenceId('user-003')
    ->givenNames('Ali')
    ->email('ali@example.com')
    ->metadata(['app_user_id' => 42, 'tier' => 'premium'])
    ->create();
```

## `get(string $id): array`

Fetch a customer by Xendit ID.

```php
$data = Xendit::customer()->get('cust_abc123');

echo $data['type'];  // 'INDIVIDUAL'
echo $data['email']; // customer email
```

## `list(string $referenceId): array`

List customers matching your reference ID.

```php
$result = Xendit::customer()->list('user-001');
// Returns Xendit list response: ['data' => [...], 'has_more' => false]
```

## `update(string $id, array $data): array`

Update a customer by Xendit ID. Returns the updated customer array.

```php
$updated = Xendit::customer()->update('cust_abc123', [
    'email'         => 'newemail@example.com',
    'mobile_number' => '+60199999999',
]);
```

## Custom Headers

All four operations (`create`, `get`, `list`, `update`) pass any headers set on the builder through to the API call.

`create()` automatically sends an `idempotency-key` header derived from the customer's `reference_id`. You can override it:

```php
// Override the auto-generated idempotency key
Xendit::customer()
    ->withHeader('idempotency-key', 'my-explicit-key')
    ->referenceId('user-001')
    ->givenNames('John')
    ->create();

// Route list/get through a sub-account
Xendit::customer()
    ->withHeader('for-user-id', 'sub-account-user-id')
    ->list('user-001');
```

## Model Queries

```php
use Laraditz\Xendit\Models\XenditCustomer;

// Find by your reference ID
$customer = XenditCustomer::referenceId('user-001')->first();

// Find by Xendit's ID
$customer = XenditCustomer::xenditId('cust_abc123')->first();

// Relationship: sessions linked to this customer
$sessions = $customer->xenditSessions;
```

## Events

| Event | Dispatched When |
|-------|----------------|
| `CustomerCreated` | After `create()` succeeds |

The event exposes `$event->customer` (an `XenditCustomer` model instance).

```php
use Laraditz\Xendit\Events\CustomerCreated;

// In EventServiceProvider
protected $listen = [
    CustomerCreated::class => [SyncCrmCustomer::class],
];
```

### Example listener

```php
namespace App\Listeners;

use Laraditz\Xendit\Events\CustomerCreated;

class SyncCrmCustomer
{
    public function handle(CustomerCreated $event): void
    {
        $customer = $event->customer;

        // Sync to your CRM
        MyCrm::upsert([
            'external_id' => $customer->reference_id,
            'xendit_id'   => $customer->xendit_id,
            'email'       => $customer->email,
        ]);
    }
}
```

## Integration with Sessions

After creating a customer you can pass their Xendit ID into a session:

```php
$customer = Xendit::customer()
    ->referenceId('user-001')
    ->givenNames('John')
    ->email('john@example.com')
    ->create();

$session = Xendit::session()
    ->referenceId('order-001')
    ->amount(100.00)
    ->sessionType('PAY')
    ->mode('PAYMENT_LINK')
    ->customerId($customer->xendit_id)
    ->create();
```

## Related Documentation

- [Session](session.md) - Creating payment sessions linked to customers
- [Webhooks](webhooks.md) - Handling payment events
