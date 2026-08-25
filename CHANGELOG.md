# Changelog

All notable changes to `xendit` will be documented in this file

## Unreleased

## 1.2.1 - 2026-08-25

### Breaking

- `RefundService::create()` return type changes from `array` to `XenditRefund` — callers using the old array shape (e.g. `$refund['id']`) must migrate to model property access (`$refund->refund_id`; note `$refund->id` is the *local* database row's own primary key, not Xendit's refund ID)

### Added

- `RefundCreated` event, dispatched after `create()` successfully persists a refund locally — mirrors the `*Created` event every other resource's `create()` already dispatches (`SessionCreated`, `CustomerCreated`). Distinct from the `RefundCreated` removed in 1.2.0: that one fired on a webhook event Xendit never sends; this one fires on real local creation

### Fixed

- `Xendit::refund()->create()` succeeded against Xendit's API but never persisted a local `XenditRefund` record — only the `refund.succeeded`/`refund.failed` webhook did, via `XenditRefund::syncFromApiResponse()`. If the webhook wasn't configured or never reached the app, no local record ever appeared despite the refund succeeding on Xendit's side. `create()` now calls `syncFromApiResponse()` itself immediately on a successful response, matching how `PaymentRequestBuilder`/`SessionBuilder` already persist locally at creation time. Webhook-driven sync is unchanged and remains idempotent against the row `create()` already wrote (updates in place, no duplicate)

---

## 1.2.0 - 2026-08-25

### Breaking

- Removed `RefundCreated` event and the `refund.created` webhook route case — Xendit's webhook `event` enum never actually contains `refund.created` (only `refund.succeeded`/`refund.failed` are real), so this was dead code that never fired in production

### Added

- `RefundFailed` event, dispatched on the (previously unhandled) `refund.failed` webhook
- `RefundReason`, `RefundStatus`, `RefundFailureCode` enums mirroring Xendit's real refund API values
- `xendit_refunds` table and `XenditRefund` model — `refund.succeeded`/`refund.failed` webhooks now auto-sync a local row via `XenditRefund::syncFromApiResponse()`, resolving `payment_id` by matching the payload's `payment_request_id` against `xendit_payments.xendit_id`
- `XenditPayment::refunds(): HasMany`
- `xendit_sessions.payment_request_id` column — previously missing entirely, despite `payment_id`/`payment_token_id` already existing
- `XenditSession::syncFromApiResponse()` — syncs `status`, `payment_id`, `payment_token_id`, `payment_request_id`, and `session_details` from a Xendit Session API/webhook response, matched by `reference_id`
- `SessionStatus::fromXenditStatus()` — maps Xendit's raw string status values (`ACTIVE`, `COMPLETED`, ...) to the int-backed `SessionStatus` enum
- `SyncSessionFromApiResponse` listener — `Xendit::session()->get()` now automatically syncs the local `xendit_sessions` row from the response, mirroring the existing transaction-sync pattern
- `XenditApiLog` model (`xendit_api_logs` table) and `LogsApiCalls` trait — every outbound `XenditClient` call is now recorded (method, endpoint, request/response payload, HTTP status, duration) whether it succeeds, fails with a non-2xx response, or fails to connect at all
- `xendit.log_api_calls` / `xendit.api_log_retention_days` config keys — `XenditApiLog` uses `Prunable` for retention via `php artisan model:prune`, no custom command needed
- `docs/logging.md`

### Changed

- `XenditClient`'s five verb methods (`get`/`post`/`put`/`patch`/`delete`) now internally call a single `request()` dispatcher wrapped in `try/finally` so API call logging can capture failures — public method signatures are unchanged, and the existing `XenditApiRequesting`/`XenditApiResponseReceived` events keep firing at exactly the same points as before

### Fixed

- `docs/refund.md` documented `payment_id` as the refund create param and free-text `reason` values; Xendit's real API requires `payment_request_id` and constrains `reason` to a fixed enum (`FRAUDULENT`, `DUPLICATE`, `REQUESTED_BY_CUSTOMER`, `CANCELLATION`, `OTHERS`) — corrected across `docs/refund.md`, `docs/webhooks.md`, and `README.md`, including response field names (`created`/`updated`, not `created_at`/`updated_at`) and webhook payload access (`$event->payload['data']`, not `$event->payload` flat)
- `WebhookHandler::handleSessionCompleted()` previously only flipped the session's status — `payment_id`/`payment_token_id`/`payment_request_id` were never actually populated from the webhook payload, even though two of those columns already existed. They're now synced via `XenditSession::syncFromApiResponse()`
- GitHub Actions CI (`main.yml`) started failing on the `prefer-lowest` matrix leg because a newer Composer version defaults `config.policy.advisories.block` to `true`, which blocks installing any package version flagged by a security advisory — incompatible with that leg's intentional installation of old Laravel 11.x versions for backward-compatibility testing. Added `config.policy.advisories.block: false` to `composer.json` (scoped to this repo's own dev/CI tooling only; not read by consumers who require this package)

---

## 1.1.0 - 2026-07-20

### Breaking

- `TransactionType` and `TransactionStatus` are now string-backed enums storing Xendit's raw API values (e.g. `TransactionStatus::Success->value === 'SUCCESS'`, `TransactionType::Payment->value === 'PAYMENT'`) instead of integer-backed values
- `xendit_transactions.status` column changed from `tinyInteger` to nullable `string` to store the raw enum value
- `xendit_transactions.payment_id` column is dropped. Replaced by nullable `source_id`/`source_type` polymorphic columns. `XenditTransaction::payment(): BelongsTo` and `XenditPayment::transactions(): HasMany` are replaced by `XenditTransaction::source(): MorphTo` and `XenditPayment::transactions()`/`XenditSession::transactions(): MorphMany`. Existing rows get `source_id`/`source_type` = `null` (no backfill).
- `XenditTransaction::syncFromApiResponse()` no longer requires a matching local `XenditPayment` — every Transaction API response is now created/updated locally regardless of whether a matching source exists (only returns `null` if the response has no `id`)

### Added

- `xendit_transactions.reference_id` column (nullable, indexed) — stores the merchant's payment reference from the Xendit Transaction API response, used to resolve the related `XenditPayment` via `external_id`
- `xendit_transactions.settlement_status`/`settled_at` columns, `SettlementStatus` enum, `XenditTransaction::markAsSettled()`, and `TransactionSettled` event
- `XenditApiRequesting`/`XenditApiResponseReceived` events fired from `XenditClient` around every API call
- `XenditTransaction::syncFromApiResponse()` and `SyncTransactionFromApiResponse` listener — `TransactionService::get()`/`list()` now automatically create-or-update local `xendit_transactions` rows (matched by `transaction_id`)
- `XenditSession::transactions(): MorphMany` — inverse of `XenditTransaction::source()`
- `XenditTransaction::scopeFromSource()`, `scopeUnlinked()`, and `linkSource()` helpers for working with the polymorphic `source` relationship
- `LinkTransactionsToSource` listener — when a `XenditPayment` or `XenditSession` is fetched via `GET /v3/payment_requests/{id}` or `GET /sessions/{id}`, any unlinked `xendit_transactions` rows matching its `reference_id` are linked to it via `source_id`/`source_type`

### Fixed

- `XenditSession` matching now tolerates the random suffix Xendit appends after an underscore to the `reference_id` we send (e.g. `XND-SESSION-...uYdW` comes back as `XND-SESSION-...uYdW_2A7-t_bd_2`) — added `XenditSession::scopeMatchingReferenceId()`, which trims everything from the first underscore before matching, and used it in `LinkTransactionsToSource`'s session branch and `WebhookHandler::handleSessionCompleted()`/`handleSessionExpired()`. These previously matched `reference_id` exactly and silently failed to find the session once Xendit appended its suffix.

---

## 1.0.4 - 2026-06-08

### Fixed

- `XenditSession` model — renamed morph relationship method from `sessionable()` to `payable()` to match the morph name used elsewhere

---

## 1.0.3 - 2026-05-26

### Changed

- `config/xendit.php` — populated `api_versions` map with known Xendit API version values based on official documentation: `payment_request => 2024-11-11`, `payment => 2024-11-11`, `payment_token => 2024-11-11`, `customer => 2020-10-31`. Session has no default set as the required version varies by region/account type.

---

## 1.0.2 - 2026-05-26

### Added

- `HasApiVersion` trait (`src/Client/Concerns/HasApiVersion.php`) — per-service `api-version` header resolution with a three-level priority chain: caller override → per-service config key → service `$defaultApiVersion` property → omit header. Uses `array_key_exists` throughout so an explicit `null` acts as a suppress signal distinct from "not set"
- `BaseBuilder::withApiVersion(?string $version)` — set or override the `api-version` header for a single request; `null` suppresses it
- `BaseBuilder::withoutApiVersion()` — sugar for `withApiVersion(null)`; suppresses the header even when a config default or service default would otherwise send it

### Changed

- **Breaking:** `config/xendit.php` — the single `api_version` key is replaced by an `api_versions` map. Applications that previously set `api_version` in their published config must re-publish (`php artisan vendor:publish --tag=xendit-config`) and migrate to the new map. No automatic fallback is provided
- `api-version` header is no longer sent on every request by default. It is only injected when explicitly configured via `config('xendit.api_versions.{service}')`, a service's `$defaultApiVersion` property, or a per-call `withApiVersion()` call
- `MakesHttpRequests::buildClient()` — removed global `api-version` injection; `buildClient()` now sets only `Authorization`, `Content-Type`, and `Accept`
- `XenditClient` — removed `empty($headers)` conditional branch; all five HTTP methods now unconditionally call `->withHeaders($headers)` (passing an empty array to Laravel's HTTP client is harmless and removes a confusing branch)
- `BaseBuilder::withHeader()` — signature widened from `string $value` to `string|null $value` so `null` can be passed as an explicit suppress marker
- All 8 services (`SessionService`, `PaymentRequestService`, `CustomerService`, `PaymentService`, `PaymentTokenService`, `RefundService`, `PaymentLinkService`, `TransactionService`) — now use `HasApiVersion` trait and call `resolveHeaders()` before forwarding headers to `XenditClient`

---

## 1.0.1 - 2026-05-25

### Fixed

- Release workflow (`create-release.yml`): changed `ref: master` to `ref: main` to match the repository's default branch
- Release workflow (`create-release.yml`): fixed awk pattern from `## \[VERSION\]` to `## VERSION` to correctly extract changelog notes matching the `## x.y.z - YYYY-MM-DD` format
- `PaymentRequestBuilder`: fixed undefined array key `payer_id` in `buildApiPayload()` when no payer information is set

### Changed

- Test infrastructure: added `$latestResponse` property to `TestCase` for easier response inspection in tests

---

## 1.0.0 - 2026-05-25

### Added

#### Core Infrastructure
- `XenditClient` — Laravel HTTP client wrapper with authentication, error handling, and full CRUD verb support (`get`, `post`, `put`, `patch`, `delete`)
- `BaseBuilder` — abstract fluent builder with `amount()`, `currency()`, `description()`, `metadata()`, `for()` (polymorphic), `withHeader()`, `withHeaders()`
- Service provider, facade (`Xendit::`), and config file (`config/xendit.php`)
- Seeders for Xendit payment channels

#### Payment Request (`Xendit::paymentRequest()`)
- Fluent builder: `amount()`, `currency()`, `description()`, `ewallets()`, `virtualAccounts()`, `qrCode()`, `card()`, `overTheCounter()`, `directDebit()`, `channelCode()`, `channelProperties()`, `successUrl()`, `failureUrl()`, `customer()`, `customerId()`, `email()`, `phone()`, `givenNames()`, `items()`, `metadata()`, `for()`, `forUserId()`, `withSplitRule()`
- `create()` — creates `XenditPayment` DB record, calls Xendit v3 API, returns updated model
- `get(string $id)` — fetch payment request status
- `cancel(string $id)` — cancel a payment request
- `simulate(string $id, array $data)` — test-mode payment simulation
- `XenditPayment` Eloquent model with `PaymentStatus` enum, scopes (`paid`, `pending`, `status`, `externalId`, `xenditId`, `forUserId`, `splitRuleId`), and helper methods (`isPaid()`, `isPending()`, `isFinal()`, `markAsPaid()`, `markAsFailed()`, `markAsExpired()`, `markAsCancelled()`)
- `xendit_payments` migration with `for_user_id` and `split_rule_id` columns

#### Payment (`Xendit::payment()`)
- `get(string $id)` — get payment status
- `cancel(string $id)` — cancel a payment
- `capture(string $id, array $data)` — capture an authorised payment

#### Payment Token (`Xendit::paymentToken()`)
- `create(array $data)` — create a payment token (saved payment method)
- `get(string $id)` — get token status
- `cancel(string $id)` — deactivate a token

#### Customer (`Xendit::customer()`)
- Fluent builder: `referenceId()`, `type()`, `givenNames()`, `surname()`, `dateOfBirth()`, `nationality()`, `gender()`, `businessName()`, `tradingName()`, `email()`, `mobileNumber()`, `phoneNumber()`, `address()`, `metadata()`, `withHeader()`, `withHeaders()`
- `create()` — creates `XenditCustomer` DB record, calls API with auto-set `idempotency-key`, returns updated model
- `get(string $id)` — fetch customer by Xendit ID
- `list(string $referenceId)` — list customers by reference ID
- `update(string $id, array $data)` — patch customer fields
- `XenditCustomer` Eloquent model with `CustomerType` enum, scopes (`referenceId`, `xenditId`), and `xenditSessions` relationship
- `xendit_customers` migration
- `CustomerCreated` event

#### Session (`Xendit::session()`)
- Fluent builder: `referenceId()`, `amount()`, `currency()`, `country()`, `sessionType()`, `mode()`, `successUrl()`, `cancelUrl()`, `customer()`, `customerId()`, `captureMethod()`, `allowSavePaymentMethod()`, `allowedPaymentChannels()`, `items()`, `locale()`, `expiresAt()`, `metadata()`, `for()`, `forUserId()`, `withSplitRule()`
- `create()` — creates `XenditSession` DB record, calls API, returns updated model; auto-generates `reference_id` if omitted
- `get(string $id)` — fetch live session data from Xendit
- `cancel(string $id)` — cancel active session; updates DB record and dispatches `SessionCanceled`
- `XenditSession` Eloquent model with `SessionStatus`, `SessionType`, `SessionMode` enums; scopes (`referenceId`, `paymentSessionId`, `active`, `completed`, `expired`, `forUserId`, `splitRuleId`); status helpers (`markAsCompleted()`, `markAsExpired()`, `markAsCanceled()`); `xenditCustomer` relationship
- `xendit_sessions` migration with `for_user_id` and `split_rule_id` columns
- `SessionCreated`, `SessionCompleted`, `SessionExpired`, `SessionCanceled` events

#### Refund (`Xendit::refund()`)
- `create(array $data)` — create a refund against a payment

#### Payment Link (`Xendit::paymentLink()`)
- `create(array $data)` — generate a shareable payment link
- `get(string $id)` — retrieve payment link details

#### Transaction (`Xendit::transaction()`)
- `get(string $id)` — get a transaction by ID
- `list(array $params)` — list transactions with optional filters

#### Webhooks
- Automatic webhook handler at `POST /xendit/webhook`
- HMAC-SHA256 signature verification
- Webhook log persistence to `xendit_webhook_logs`
- Handles: `payment.succeeded`, `payment.failed`, `payment.expired`, `payment_token.created`, `payment_token.activated`, `refund.created`, `refund.succeeded`, `payment_session.completed`, `payment_session.expired`
- Dispatches `PaymentPaid`, `PaymentFailed`, `PaymentExpired`, `PaymentTokenCreated`, `PaymentTokenActivated`, `RefundCreated`, `RefundSucceeded`, `SessionCompleted`, `SessionExpired` events

#### Custom HTTP Headers
- `BaseBuilder::withHeader(string $key, string $value)` — set a single request header on any builder
- `BaseBuilder::withHeaders(array $headers)` — merge multiple headers at once
- `SessionBuilder::forUserId(string $userId)` — sets `for-user-id` header and persists to `xendit_sessions.for_user_id`
- `SessionBuilder::withSplitRule(string $ruleId)` — sets `with-split-rule` header and persists to `xendit_sessions.split_rule_id`
- `PaymentRequestBuilder::forUserId(string $userId)` — sets `for-user-id` header and persists to `xendit_payments.for_user_id`
- `PaymentRequestBuilder::withSplitRule(string $ruleId)` — sets `with-split-rule` header and persists to `xendit_payments.split_rule_id`
- Headers thread through the full stack: builder → service → `XenditClient`; caller-supplied headers merge over service defaults (caller wins)
