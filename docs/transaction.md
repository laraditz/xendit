# Transaction

The Transaction API provides a unified view of all financial transactions in your Xendit account. Use it to query individual transactions or list all transactions with filtering options.

**Official Documentation:** https://docs.xendit.co/apidocs/get-transaction

## Available Methods

### `get(string $id): array`

Get a specific transaction by its ID.

**Official API:** `GET /transactions/{id}`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Transaction ID from Xendit |

**Example:**

```php
use Laraditz\Xendit\Facades\Xendit;

$transaction = Xendit::transaction()->get('txn_12345678');

// Response structure
[
    'id' => 'txn_12345678',
    'type' => 'PAYMENT',
    'status' => 'SUCCESS',
    'amount' => 100000,
    'currency' => 'MYR',
    'reference_id' => 'ORDER-123',
    'payment_method' => [
        'type' => 'EWALLET',
        'channel_code' => 'SHOPEEPAY',
    ],
    'created_at' => '2024-01-15T10:00:00Z',
    ...
]
```

### `list(array $params = []): array`

List all transactions with optional filtering.

**Official API:** `GET /transactions`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `types` | array | No | Filter by transaction types |
| `statuses` | array | No | Filter by transaction statuses |
| `channel_codes` | array | No | Filter by payment channel codes |
| `reference_id` | string | No | Filter by your reference ID |
| `created_after` | string | No | Filter transactions created after date (ISO 8601) |
| `created_before` | string | No | Filter transactions created before date (ISO 8601) |
| `limit` | number | No | Number of results per page (default: 10, max: 50) |
| `after_id` | string | No | Cursor for pagination |
| `before_id` | string | No | Cursor for pagination (reverse) |

**Example:**

```php
use Laraditz\Xendit\Facades\Xendit;

// List all transactions
$transactions = Xendit::transaction()->list();

// List with filters
$transactions = Xendit::transaction()->list([
    'types' => ['PAYMENT', 'REFUND'],
    'statuses' => ['SUCCESS'],
    'limit' => 20,
]);

// List transactions for a specific order
$transactions = Xendit::transaction()->list([
    'reference_id' => 'ORDER-123',
]);

// List transactions within date range
$transactions = Xendit::transaction()->list([
    'created_after' => '2024-01-01T00:00:00Z',
    'created_before' => '2024-01-31T23:59:59Z',
    'limit' => 50,
]);

// Response structure
[
    'data' => [
        [
            'id' => 'txn_12345678',
            'type' => 'PAYMENT',
            'status' => 'SUCCESS',
            'amount' => 100000,
            ...
        ],
        // ... more transactions
    ],
    'has_more' => true,
    'links' => [
        'next' => '...',
        'prev' => '...',
    ],
]
```

## Usage Examples

### Example 1: View Transaction Details

```php
use Laraditz\Xendit\Facades\Xendit;

public function showTransaction($transactionId)
{
    try {
        $transaction = Xendit::transaction()->get($transactionId);

        return view('transactions.show', [
            'transaction' => $transaction,
        ]);

    } catch (\Exception $e) {
        return back()->withError('Transaction not found');
    }
}
```

### Example 2: List All Payments

```php
use Laraditz\Xendit\Facades\Xendit;

public function listPayments()
{
    $transactions = Xendit::transaction()->list([
        'types' => ['PAYMENT'],
        'statuses' => ['SUCCESS'],
        'limit' => 50,
    ]);

    return view('admin.payments', [
        'payments' => $transactions['data'],
        'hasMore' => $transactions['has_more'],
    ]);
}
```

### Example 3: Generate Financial Report

```php
use Laraditz\Xendit\Facades\Xendit;

public function generateMonthlyReport($year, $month)
{
    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();

    // Fetch all transactions for the month
    $allTransactions = [];
    $afterId = null;

    do {
        $params = [
            'created_after' => $startDate->toISOString(),
            'created_before' => $endDate->toISOString(),
            'limit' => 50,
        ];

        if ($afterId) {
            $params['after_id'] = $afterId;
        }

        $response = Xendit::transaction()->list($params);
        $allTransactions = array_merge($allTransactions, $response['data']);

        $afterId = $response['has_more'] ? end($response['data'])['id'] : null;

    } while ($afterId);

    // Calculate totals
    $report = [
        'period' => $startDate->format('F Y'),
        'total_transactions' => count($allTransactions),
        'total_payments' => 0,
        'total_refunds' => 0,
        'payment_count' => 0,
        'refund_count' => 0,
        'by_channel' => [],
    ];

    foreach ($allTransactions as $txn) {
        if ($txn['type'] === 'PAYMENT' && $txn['status'] === 'SUCCESS') {
            $report['total_payments'] += $txn['amount'];
            $report['payment_count']++;

            $channel = $txn['payment_method']['channel_code'] ?? 'UNKNOWN';
            $report['by_channel'][$channel] = ($report['by_channel'][$channel] ?? 0) + $txn['amount'];
        }

        if ($txn['type'] === 'REFUND' && $txn['status'] === 'SUCCESS') {
            $report['total_refunds'] += $txn['amount'];
            $report['refund_count']++;
        }
    }

    $report['net_revenue'] = $report['total_payments'] - $report['total_refunds'];

    return view('admin.reports.financial', compact('report', 'allTransactions'));
}
```

### Example 4: Reconciliation System

```php
use Laraditz\Xendit\Facades\Xendit;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class ReconciliationJob
{
    public function handle()
    {
        // Get yesterday's date range
        $yesterday = now()->subDay();
        $startDate = $yesterday->startOfDay()->toISOString();
        $endDate = $yesterday->endOfDay()->toISOString();

        // Fetch all successful payments from yesterday
        $transactions = $this->fetchAllTransactions([
            'types' => ['PAYMENT'],
            'statuses' => ['SUCCESS'],
            'created_after' => $startDate,
            'created_before' => $endDate,
        ]);

        $mismatches = [];

        foreach ($transactions as $txn) {
            $referenceId = $txn['reference_id'] ?? null;

            if (!$referenceId) {
                $mismatches[] = [
                    'transaction_id' => $txn['id'],
                    'issue' => 'No reference ID',
                ];
                continue;
            }

            // Find order in database
            $order = Order::where('external_id', $referenceId)->first();

            if (!$order) {
                $mismatches[] = [
                    'transaction_id' => $txn['id'],
                    'reference_id' => $referenceId,
                    'issue' => 'Order not found in database',
                ];
                continue;
            }

            // Check amount matches
            if ($order->total != $txn['amount']) {
                $mismatches[] = [
                    'transaction_id' => $txn['id'],
                    'order_id' => $order->id,
                    'issue' => 'Amount mismatch',
                    'expected' => $order->total,
                    'actual' => $txn['amount'],
                ];
                continue;
            }

            // Check if marked as paid
            if (!$order->isPaid()) {
                Log::warning('Order not marked as paid, updating', [
                    'order_id' => $order->id,
                    'transaction_id' => $txn['id'],
                ]);

                $order->markAsPaid();
            }
        }

        // Send reconciliation report
        if (!empty($mismatches)) {
            Mail::to(config('mail.finance'))->send(
                new ReconciliationReportMail($mismatches)
            );
        }

        Log::info('Reconciliation completed', [
            'date' => $yesterday->toDateString(),
            'transactions_checked' => count($transactions),
            'mismatches' => count($mismatches),
        ]);
    }

    protected function fetchAllTransactions(array $params): array
    {
        $allTransactions = [];
        $afterId = null;

        do {
            if ($afterId) {
                $params['after_id'] = $afterId;
            }

            $response = Xendit::transaction()->list(array_merge($params, ['limit' => 50]));
            $allTransactions = array_merge($allTransactions, $response['data']);

            $afterId = $response['has_more'] ? end($response['data'])['id'] : null;

        } while ($afterId);

        return $allTransactions;
    }
}
```

### Example 5: Real-time Transaction Dashboard

```php
use Laraditz\Xendit\Facades\Xendit;

public function getDashboardStats()
{
    // Today's transactions
    $today = Xendit::transaction()->list([
        'created_after' => now()->startOfDay()->toISOString(),
        'limit' => 50,
    ]);

    $stats = [
        'today' => [
            'total_amount' => 0,
            'payment_count' => 0,
            'refund_count' => 0,
            'by_channel' => [],
        ],
    ];

    foreach ($today['data'] as $txn) {
        if ($txn['type'] === 'PAYMENT' && $txn['status'] === 'SUCCESS') {
            $stats['today']['total_amount'] += $txn['amount'];
            $stats['today']['payment_count']++;

            $channel = $txn['payment_method']['channel_code'] ?? 'OTHER';
            $stats['today']['by_channel'][$channel] =
                ($stats['today']['by_channel'][$channel] ?? 0) + 1;
        }

        if ($txn['type'] === 'REFUND' && $txn['status'] === 'SUCCESS') {
            $stats['today']['refund_count']++;
        }
    }

    // Get recent transactions
    $stats['recent_transactions'] = array_slice($today['data'], 0, 10);

    return response()->json($stats);
}
```

### Example 6: Export Transactions to CSV

```php
use Laraditz\Xendit\Facades\Xendit;
use Illuminate\Support\Facades\Storage;

public function exportTransactions(Request $request)
{
    $params = [
        'created_after' => $request->start_date,
        'created_before' => $request->end_date,
        'limit' => 50,
    ];

    // Fetch all transactions
    $allTransactions = [];
    $afterId = null;

    do {
        if ($afterId) {
            $params['after_id'] = $afterId;
        }

        $response = Xendit::transaction()->list($params);
        $allTransactions = array_merge($allTransactions, $response['data']);

        $afterId = $response['has_more'] ? end($response['data'])['id'] : null;

    } while ($afterId);

    // Generate CSV
    $csv = [];
    $csv[] = [
        'Transaction ID',
        'Type',
        'Status',
        'Amount',
        'Currency',
        'Payment Method',
        'Channel',
        'Reference ID',
        'Created At',
    ];

    foreach ($allTransactions as $txn) {
        $csv[] = [
            $txn['id'],
            $txn['type'],
            $txn['status'],
            $txn['amount'],
            $txn['currency'],
            $txn['payment_method']['type'] ?? '',
            $txn['payment_method']['channel_code'] ?? '',
            $txn['reference_id'] ?? '',
            $txn['created_at'],
        ];
    }

    // Save to storage
    $filename = 'transactions_' . now()->format('Y-m-d_His') . '.csv';
    $fp = fopen(storage_path("app/{$filename}"), 'w');

    foreach ($csv as $row) {
        fputcsv($fp, $row);
    }

    fclose($fp);

    return response()->download(storage_path("app/{$filename}"))
        ->deleteFileAfterSend();
}
```

### Example 7: Track Payment Method Usage

```php
use Laraditz\Xendit\Facades\Xendit;

public function getPaymentMethodStats($days = 30)
{
    $startDate = now()->subDays($days)->startOfDay();

    $transactions = Xendit::transaction()->list([
        'types' => ['PAYMENT'],
        'statuses' => ['SUCCESS'],
        'created_after' => $startDate->toISOString(),
        'limit' => 50,
    ]);

    $stats = [
        'by_type' => [],
        'by_channel' => [],
        'total_transactions' => 0,
        'total_amount' => 0,
    ];

    // Fetch all transactions
    $allTransactions = $this->fetchAllPages($transactions);

    foreach ($allTransactions as $txn) {
        $type = $txn['payment_method']['type'] ?? 'UNKNOWN';
        $channel = $txn['payment_method']['channel_code'] ?? 'UNKNOWN';

        // By type
        if (!isset($stats['by_type'][$type])) {
            $stats['by_type'][$type] = [
                'count' => 0,
                'amount' => 0,
            ];
        }
        $stats['by_type'][$type]['count']++;
        $stats['by_type'][$type]['amount'] += $txn['amount'];

        // By channel
        if (!isset($stats['by_channel'][$channel])) {
            $stats['by_channel'][$channel] = [
                'count' => 0,
                'amount' => 0,
            ];
        }
        $stats['by_channel'][$channel]['count']++;
        $stats['by_channel'][$channel]['amount'] += $txn['amount'];

        $stats['total_transactions']++;
        $stats['total_amount'] += $txn['amount'];
    }

    // Calculate percentages
    foreach ($stats['by_type'] as $type => &$data) {
        $data['percentage'] = ($data['count'] / $stats['total_transactions']) * 100;
    }

    foreach ($stats['by_channel'] as $channel => &$data) {
        $data['percentage'] = ($data['count'] / $stats['total_transactions']) * 100;
    }

    return view('admin.analytics.payment-methods', compact('stats'));
}
```

## Transaction Types

| Type | Description |
|------|-------------|
| `PAYMENT` | Payment transaction |
| `REFUND` | Refund transaction |
| `DISBURSEMENT` | Disbursement/payout transaction |
| `REVERSAL` | Payment reversal |

## Transaction Statuses

| Status | Description |
|--------|-------------|
| `PENDING` | Transaction is being processed |
| `SUCCESS` | Transaction completed successfully |
| `FAILED` | Transaction failed |
| `REVERSED` | Transaction was reversed |

## Pagination

When listing transactions, use the pagination cursors for efficient data retrieval:

```php
// First page
$page1 = Xendit::transaction()->list(['limit' => 20]);

// Next page
if ($page1['has_more']) {
    $lastId = end($page1['data'])['id'];
    $page2 = Xendit::transaction()->list([
        'limit' => 20,
        'after_id' => $lastId,
    ]);
}

// Previous page (reverse pagination)
if ($page2['has_more']) {
    $firstId = $page2['data'][0]['id'];
    $page1Again = Xendit::transaction()->list([
        'limit' => 20,
        'before_id' => $firstId,
    ]);
}
```

## Best Practices

### 1. Use Appropriate Filters

```php
// ❌ Bad: Fetch everything without filters
$transactions = Xendit::transaction()->list(['limit' => 50]);

// ✅ Good: Use filters to get what you need
$transactions = Xendit::transaction()->list([
    'types' => ['PAYMENT'],
    'statuses' => ['SUCCESS'],
    'created_after' => now()->subDays(7)->toISOString(),
    'limit' => 50,
]);
```

### 2. Handle Pagination Properly

```php
// Helper function to fetch all pages
function fetchAllTransactions(array $params): array
{
    $allTransactions = [];
    $afterId = null;

    do {
        if ($afterId) {
            $params['after_id'] = $afterId;
        }

        $response = Xendit::transaction()->list(array_merge($params, ['limit' => 50]));
        $allTransactions = array_merge($allTransactions, $response['data']);

        $afterId = $response['has_more'] ? end($response['data'])['id'] : null;

    } while ($afterId);

    return $allTransactions;
}
```

### 3. Cache Transaction Queries

```php
use Illuminate\Support\Facades\Cache;

public function getTodaysTransactions()
{
    $cacheKey = 'transactions_today_' . now()->format('Y-m-d');

    return Cache::remember($cacheKey, 300, function() {
        return Xendit::transaction()->list([
            'created_after' => now()->startOfDay()->toISOString(),
            'limit' => 50,
        ]);
    });
}
```

### 4. Store Transaction References

```php
// Store transaction ID in your database
$order->update([
    'xendit_transaction_id' => $transaction['id'],
    'payment_channel' => $transaction['payment_method']['channel_code'],
    'paid_at' => $transaction['created_at'],
]);

// Later, verify transaction
$verifiedTxn = Xendit::transaction()->get($order->xendit_transaction_id);
```

### 5. Implement Error Handling

```php
try {
    $transaction = Xendit::transaction()->get($transactionId);
} catch (\Laraditz\Xendit\Exceptions\NotFoundException $e) {
    Log::warning('Transaction not found', ['id' => $transactionId]);
    return back()->withError('Transaction not found');
} catch (\Exception $e) {
    Log::error('Failed to fetch transaction', [
        'id' => $transactionId,
        'error' => $e->getMessage(),
    ]);
    return back()->withError('Unable to fetch transaction details');
}
```

### 6. Monitor for Anomalies

```php
// Scheduled job to detect unusual transaction patterns
class TransactionMonitoringJob
{
    public function handle()
    {
        $transactions = Xendit::transaction()->list([
            'created_after' => now()->subHour()->toISOString(),
            'statuses' => ['SUCCESS'],
        ]);

        $alerts = [];

        // Check for large transactions
        foreach ($transactions['data'] as $txn) {
            if ($txn['amount'] > 1000000) { // Alert for transactions > 10,000 MYR
                $alerts[] = "Large transaction: {$txn['id']} - " . formatCurrency($txn['amount']);
            }
        }

        // Check for rapid transactions from same reference
        $referenceCount = [];
        foreach ($transactions['data'] as $txn) {
            $ref = $txn['reference_id'] ?? 'unknown';
            $referenceCount[$ref] = ($referenceCount[$ref] ?? 0) + 1;
        }

        foreach ($referenceCount as $ref => $count) {
            if ($count > 5) { // Alert for more than 5 transactions/hour
                $alerts[] = "Rapid transactions for reference: {$ref} ({$count} transactions)";
            }
        }

        if (!empty($alerts)) {
            Mail::to(config('mail.security'))->send(
                new TransactionAlertMail($alerts)
            );
        }
    }
}
```

## Related Documentation

- [Payment](payment.md) - Managing payments
- [Refund](refund.md) - Processing refunds
- [Payment Request](payment-request.md) - Creating payments
