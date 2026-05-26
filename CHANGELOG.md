# Changelog

All notable changes to `xendit` will be documented in this file

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
