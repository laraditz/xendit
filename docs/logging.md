# Logging

This package can record an audit trail of every outbound API call it makes.

## API Request/Response Logging

Outbound calls made via `XenditClient` — across Payment Requests, Payments, Refunds, Sessions, Customers, Payment Tokens, Payment Links, and Transactions — are recorded for troubleshooting and reconciliation, whether the call succeeds, fails with a non-2xx response, or fails to connect at all.

**Local Record:** `XenditApiLog` (`xendit_api_logs` table). See the model source for the exact fields captured.

Payloads are stored as-is — no redaction is applied. If your request/response payloads may contain sensitive data, keep that in mind when granting access to this table.

### Disabling

```env
XENDIT_LOG_API_CALLS=false
```

### Retention

```env
XENDIT_API_LOG_RETENTION_DAYS=30
```

Rows older than this are eligible for deletion via Laravel's built-in pruning — no custom command needed:

```php
// routes/console.php or app/Console/Kernel.php
Schedule::command('model:prune')->daily();
```

## Usage Example

```php
use Laraditz\Xendit\Models\XenditApiLog;

// Find the slowest outbound calls today
XenditApiLog::whereDate('created_at', today())
    ->orderByDesc('duration_ms')
    ->limit(10)
    ->get();

// Find recent failed calls
XenditApiLog::where('http_status', '>=', 400)
    ->orWhereNull('http_status') // connection failures never reached a response
    ->latest()
    ->get(['method', 'endpoint', 'http_status', 'created_at']);

// Find every call related to a specific refund
XenditApiLog::where('reference_id', 'rfd-12345678')->get();
```

## Related Documentation

- [Webhooks](webhooks.md) — inbound webhook logging (`XenditWebhookLog`) is documented separately there.
