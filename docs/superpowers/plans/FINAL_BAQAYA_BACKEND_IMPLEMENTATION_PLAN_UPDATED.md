# BAQAYA BACKEND — FINAL PRODUCTION IMPLEMENTATION PLAN
## Laravel 12 + Offline First + Financially Safe Architecture

---

# PROJECT GOAL

Build a production-grade backend for Baqaya:

- Offline-first mobile ledger application
- Pakistani shop owners
- Customer debt/credit tracking
- Add ledger entries
- Add payments
- Manage customers
- User-facing transaction/payment edit screens
- WhatsApp reminders
- Multi-device synchronization
- Financially safe accounting system
- Cursor-based sync architecture
- Queue-driven scalable infrastructure

---

# CORE TECH STACK

## Backend

- Laravel 12
- PHP 8.2+
- MySQL 8+
- Redis
- Laravel Sanctum
- Laravel Horizon
- Pest
- Larastan
- Pint
- DomPDF

---

# CORE ARCHITECTURE PRINCIPLES

---

## 1. SERVER AUTHORITATIVE SYSTEM

The backend is the source of truth.

Frontend NEVER controls:

- balances
- ledger state
- sync versions
- timestamps
- financial calculations

---

## 2. IMMUTABLE FINANCIAL HISTORY

Financial records are append-only.

NEVER:
- directly edit financial transaction amount
- directly edit payment amount
- hard delete transactions
- hard delete payments
- overwrite balances

Instead use:
- reversals
- corrections
- adjustments

---

## 3. USER-FACING EDIT SCREENS ARE ALLOWED

The mobile app can have screens called:

```text
Edit Transaction
Edit Payment
```

But backend must not mutate the original financial record.

When a user edits a financial field, the backend must internally create correction records.

Example:

```text
Original transaction: credit 1000
User edits amount to: credit 800

Backend:
1. Keep original record
2. Mark original as corrected
3. Create reversal entry for -1000
4. Create corrected transaction for +800
5. Recalculate balance safely
```

So the user experience remains simple, but the financial ledger remains clean.

---

## 4. OFFLINE-FIRST DESIGN

Clients:
- save locally first
- sync later

Backend:
- validates
- resolves conflicts
- assigns server versions
- handles idempotency

---

## 5. SOFT DELETE ONLY

All syncable entities must use:

```php
SoftDeletes
```

NEVER hard delete:
- customers
- ledger entries
- payments
- transactions

---

## 6. IDEMPOTENT SYNC

Duplicate sync operations must NEVER:
- duplicate balances
- duplicate payments
- duplicate ledger entries

Every sync request MUST include:

```json
{
  "request_id": "uuid",
  "operation_id": "uuid"
}
```

---

# FINAL DATABASE ARCHITECTURE

# Core Tables

```text
users
devices
refresh_tokens
otp_codes

shops
shop_users

customers

ledger_entries
transactions
transaction_reversals
transaction_corrections
payment_corrections

sync_requests
sync_operations
sync_cursors

reminders
reminder_logs

customer_statement_pdfs

failed_syncs
audit_logs
```

---

# REQUIRED GLOBAL COLUMNS

Every syncable entity MUST contain:

```text
uuid
server_version
created_at
updated_at
deleted_at
```

---

# REQUIRED FINANCIAL CORRECTION COLUMNS

Transactions and payments should support correction metadata:

```text
status
reversal_of_transaction_id
corrected_by_transaction_id
correction_reason
corrected_at
corrected_by
device_id
sync_operation_id
```

Recommended statuses:

```text
active
corrected
reversed
voided
```

---

# REQUIRED INDEXES

## Customers

```sql
INDEX(shop_id)
INDEX(server_version)
INDEX(phone)
INDEX(deleted_at)
```

---

## Transactions

```sql
INDEX(shop_id)
INDEX(customer_id)
INDEX(server_version)
INDEX(transaction_date)
INDEX(status)
INDEX(deleted_at)
INDEX(reversal_of_transaction_id)
INDEX(corrected_by_transaction_id)
```

---

## Sync

```sql
INDEX(request_id)
INDEX(operation_id)
INDEX(device_id)
```

---

# FINAL FOLDER STRUCTURE

```text
app/
 ├── Domain/
 │    ├── Auth/
 │    ├── Device/
 │    ├── Shop/
 │    ├── Customer/
 │    ├── Ledger/
 │    ├── Transaction/
 │    ├── Payment/
 │    ├── Sync/
 │    ├── Reminder/
 │    └── Statement/
 │
 ├── Actions/
 ├── DTOs/
 ├── Services/
 ├── Policies/
 ├── Events/
 ├── Listeners/
 ├── Jobs/
 ├── Support/
 │
 ├── Http/
 │    ├── Controllers/Api/V1
 │    ├── Requests
 │    ├── Resources
 │    └── Middleware
```

---

# IMPLEMENTATION ORDER

---

# PHASE 1 — FOUNDATION SETUP

# Goal

Prepare production-grade infrastructure.

---

## Install Packages

```bash
composer require laravel/sanctum
composer require laravel/horizon
composer require pestphp/pest --dev
composer require pestphp/pest-plugin-laravel --dev
composer require larastan/larastan --dev
composer require barryvdh/laravel-dompdf
```

---

## Configure Infrastructure

Setup:

- Redis
- Horizon
- Queue workers
- Pest
- Pint
- Larastan
- API response helper
- Exception formatting
- Logging
- Rate limiting

---

## Queue Architecture

Separate queues:

```text
high
default
sync
notifications
pdf
```

CRITICAL:
sync queue must NEVER be blocked by PDF generation.

---

## Monitoring Stack

Install:

```text
Laravel Telescope
Sentry
Horizon Dashboard
```

---

# PHASE 2 — DATABASE FOUNDATION

# Goal

Create scalable schema.

---

## Create Core Tables

```text
users
devices
refresh_tokens
otp_codes

shops
shop_users

customers

ledger_entries
transactions
transaction_reversals
transaction_corrections
payment_corrections

sync_requests
sync_operations
sync_cursors

audit_logs
failed_syncs
```

---

## Public IDs

Use:

```text
ULID
```

Never expose incremental IDs.

---

# PHASE 3 — AUTHENTICATION SYSTEM

# Goal

Secure multi-device auth system.

---

## APIs

```text
POST /api/v1/auth/otp/request
POST /api/v1/auth/otp/verify
POST /api/v1/auth/token/refresh
POST /api/v1/auth/logout
POST /api/v1/auth/logout-all
```

---

## Authentication Flow

```text
Request OTP
→ Verify OTP
→ Create device
→ Create Sanctum token
→ Create refresh token
→ Return auth session
```

---

## Security Rules

Always:

- hash OTPs
- hash refresh tokens
- bind tokens to devices
- revoke old refresh tokens
- rate limit OTP requests

---

## Device Tracking Fields

```text
device_uuid
platform
device_name
fcm_token
last_seen_at
last_sync_at
revoked_at
```

---

# PHASE 4 — SHOP SYSTEM

# Goal

Multi-tenant architecture.

---

## APIs

```text
POST /api/v1/shop
GET /api/v1/shop
PATCH /api/v1/shop
DELETE /api/v1/shop
```

---

## Middleware

```text
ResolveShop
EnsureShopAccess
```

---

## Rules

Everything belongs to a shop:

- customers
- transactions
- ledger entries
- payments
- reminders
- sync records

---

# PHASE 5 — CUSTOMER MODULE

# Goal

Offline-ready customer management.

---

## APIs

```text
GET /api/v1/customers
POST /api/v1/customers
POST /api/v1/customers/bulk
GET /api/v1/customers/{uuid}
PATCH /api/v1/customers/{uuid}
DELETE /api/v1/customers/{uuid}
```

---

## Customer Features

Support:

- pagination
- search
- soft delete
- sync metadata
- bulk import
- offline timestamps

---

## Rules

Never:
- hard delete customers

Always:
- increment server_version
- validate unique phone per shop

---

# PHASE 6 — LEDGER + PAYMENT ENGINE

# Goal

Support add ledger and add payment while preserving immutable financial history.

---

## User-Facing Features

The app includes:

```text
Manage Customers
Add Ledger
Add Payment
Edit Transaction
Edit Payment
```

Backend must support all of these, but edit screens must use correction logic.

---

## Transaction Types

```text
credit
payment
adjustment
reversal
correction
```

---

## Financial APIs

```text
GET /api/v1/transactions
POST /api/v1/transactions
GET /api/v1/transactions/{uuid}

POST /api/v1/transactions/{uuid}/correction
POST /api/v1/transactions/{uuid}/reverse
POST /api/v1/transactions/adjustment

POST /api/v1/payments
GET /api/v1/payments
GET /api/v1/payments/{uuid}
POST /api/v1/payments/{uuid}/correction
POST /api/v1/payments/{uuid}/reverse
```

---

## Do NOT Implement Unsafe Financial Mutation

Do not use these for financial fields:

```text
PATCH /api/v1/transactions/{uuid}
PATCH /api/v1/payments/{uuid}
DELETE /api/v1/transactions/{uuid}
DELETE /api/v1/payments/{uuid}
```

---

## Optional Metadata-Only PATCH

PATCH is only allowed for non-financial metadata:

```text
notes
reference_no
attachment
category
description
```

Allowed endpoints:

```text
PATCH /api/v1/transactions/{uuid}/metadata
PATCH /api/v1/payments/{uuid}/metadata
```

These endpoints must NOT allow changes to:

```text
amount
type
customer_id
payment amount
transaction direction
balance impact
transaction_date if it affects historical calculation
```

---

# PHASE 7 — TRANSACTION/PAYMENT CORRECTION FLOW

# Goal

Allow edit screens without corrupting ledger history.

---

## Edit Transaction Flow

When user edits amount/type/customer/date/payment-impacting field:

```text
Client calls correction endpoint
Backend locks customer balance
Backend validates original transaction belongs to shop
Backend creates reversal entry for original transaction
Backend creates corrected transaction
Backend marks original transaction as corrected
Backend recalculates affected customer balance
Backend writes audit log
Backend returns corrected transaction
```

---

## Example

Original:

```text
credit 1000
```

User edits:

```text
credit 800
```

Backend creates:

```text
original credit 1000 status=corrected
reversal -1000 reversal_of_transaction_id=original_id
corrected credit 800 corrected_by_transaction_id=new_id
```

Net correction:

```text
-200
```

---

## Edit Payment Flow

Original:

```text
payment 500
```

User edits:

```text
payment 700
```

Backend creates:

```text
original payment 500 status=corrected
reversal +500 reversal_of_payment_id=original_id
corrected payment 700
```

Net correction:

```text
-200 from customer balance
```

---

## Correction API Payload

```json
{
  "amount": 800,
  "type": "credit",
  "customer_uuid": "customer-ulid",
  "transaction_date": "2026-05-17",
  "notes": "Corrected wrong amount",
  "correction_reason": "Wrong amount entered"
}
```

---

## Correction Rules

Always:

- require correction_reason
- preserve original transaction
- create reversal entry
- create corrected transaction
- update server_version
- log audit trail
- return new balance

Never:

- update amount directly on original record
- delete original record
- hide correction from sync

---

# PHASE 8 — FINANCIAL SAFETY LAYER

# Goal

Prevent corruption and race conditions.

---

## Add Redis Locks

Required for:

- balance updates
- sync processing
- transaction correction
- payment correction
- transaction reversal
- customer recalculation

Example lock key:

```text
customer-balance:{customer_uuid}
```

---

## Balance Calculation Rules

Balance updates MUST use:

```php
DB::transaction(function () {
    // create transaction/payment/correction/reversal
    // update customer balance
    // write audit log
});
```

AND:

```php
Cache::lock()
```

to prevent concurrent corruption.

---

## Add Audit Logging

Track:

```text
created_by
device_id
ip_address
action_type
entity_type
entity_id
sync_origin
old_values
new_values
```

---

# PHASE 9 — OFFLINE SYNC SYSTEM

# Goal

True offline-first architecture.

---

# CRITICAL

You MUST implement BOTH:

```text
sync/push
sync/pull
```

Not only pull.

---

# Push Sync API

```text
POST /api/v1/sync/push
```

---

# Pull Sync API

```text
GET /api/v1/sync/pull
```

---

# Push Sync Flow

```text
Client queues operations
→ internet restored
→ send batch
→ validate request_id
→ validate operation_id
→ process sequentially
→ return results
```

---

# Pull Sync Flow

```text
Client sends cursor
→ server returns changes
→ client updates local DB
→ cursor updated locally
```

---

# Sync Operation Types

Support these sync operations:

```text
customer.create
customer.update
customer.delete

transaction.create
transaction.correct
transaction.reverse
transaction.metadata_update

payment.create
payment.correct
payment.reverse
payment.metadata_update

reminder.create
```

---

# Sync Conflict Strategy

```text
Last write wins
+ server_version validation
```

---

# Conflict Response

Return:

```json
{
  "code": "SYNC_CONFLICT",
  "latest_server_version": 123
}
```

---

# Sync Retry Strategy

```text
1 second
5 seconds
15 seconds
30 seconds
60 seconds
```

---

# PHASE 10 — IDEMPOTENCY LAYER

# Goal

Prevent duplicate financial operations.

---

# Request Idempotency

Every batch MUST include:

```text
request_id
```

Duplicate request:

```text
Return cached response
```

---

# Operation Idempotency

Every operation MUST include:

```text
operation_id
```

Duplicate operation:

```text
Ignore duplicate execution
```

---

# PHASE 11 — PDF STATEMENTS

# Goal

Customer statement generation.

---

## APIs

```text
POST /api/v1/customers/{uuid}/statement
GET /api/v1/statements/{uuid}/download
```

---

## Rules

Generate PDFs through queues ONLY.

Never generate PDFs inside request lifecycle.

---

# PHASE 12 — WHATSAPP REMINDER SYSTEM

# Goal

Automated customer reminders.

---

## APIs

```text
POST /api/v1/reminders/send
GET /api/v1/reminders
```

---

## Reminder Flow

```text
Create reminder
→ queue job
→ send WhatsApp
→ store delivery log
→ retry failures
```

---

# PHASE 13 — SECURITY HARDENING

# Goal

Production security.

---

# Rate Limiting

Protect:

```text
OTP endpoints
sync endpoints
transaction endpoints
payment endpoints
statement endpoints
```

---

# Never

- trust client timestamps
- expose internal IDs
- bypass middleware
- bypass sync validation
- bypass financial transactions
- directly edit financial history

---

# Always

- validate ownership
- validate device
- validate shop access
- use queues
- use DB transactions
- use Redis locks
- use correction/reversal for financial edits

---

# PHASE 14 — TESTING STRATEGY

# Goal

Prevent production regressions.

---

## Required Test Types

- auth tests
- profile tests
- shop tests
- customer tests
- ledger tests
- payment tests
- transaction correction tests
- payment correction tests
- sync push tests
- sync pull tests
- idempotency tests
- queue tests
- PDF tests

---

## Critical Financial Tests

Must test:

```text
creating ledger increases/decreases balance correctly
creating payment updates balance correctly
editing transaction creates reversal + corrected entry
editing payment creates reversal + corrected payment
duplicate sync operation does not duplicate payment
duplicate sync operation does not duplicate ledger entry
correction appears in pull sync
original transaction remains preserved
metadata-only update does not change balance
```

---

## Required Tools

```text
Pest
Larastan
Pint
```

---

# Required CI Checks

```bash
php artisan test
vendor/bin/phpstan analyse
vendor/bin/pint
```

---

# PHASE 15 — OBSERVABILITY

# Goal

Production debugging + monitoring.

---

## Required Logging

Track:

- failed syncs
- duplicate operations
- payment anomalies
- queue failures
- auth anomalies
- transaction corrections
- payment corrections
- reversal creation

---

## Required Monitoring

```text
Horizon
Telescope
Sentry
```

---

# FINAL API STRUCTURE

```text
/api/v1/auth/*
/api/v1/profile/*
/api/v1/shop/*
/api/v1/customers/*
/api/v1/transactions/*
/api/v1/payments/*
/api/v1/sync/*
/api/v1/reminders/*
/api/v1/statements/*
```

---

# FINAL ENGINEERING RULES

# NEVER

- edit transaction amount directly
- edit payment amount directly
- hard delete financial data
- trust frontend balances
- duplicate sync business logic
- bypass Redis locks
- process sync without idempotency
- hide corrections from sync

---

# ALWAYS

- use immutable ledger entries
- use correction/reversal for financial edits
- use DB transactions
- use queues
- use soft deletes
- use server_version
- use audit logging
- validate device ownership
- validate shop ownership

---

# MOST IMPORTANT ENGINEERING RULE

Build features in THIS order:

```text
auth
→ shop
→ customers
→ add ledger
→ add payment
→ correction/reversal edit flow
→ sync
→ reminders
→ reporting
```

DO NOT build sync early.

If your normal APIs are unstable,
sync multiplies the instability by 100x.

---

# FINAL MVP MILESTONE

The backend is production-ready when:

```text
User logs in with OTP
→ device registered
→ shop created
→ customers managed
→ ledger entries added
→ payments added
→ edit transaction screen works through correction flow
→ edit payment screen works through correction flow
→ original financial records remain preserved
→ balances remain correct
→ sync works across devices
→ duplicate sync requests do not corrupt finance
→ PDF statements generate asynchronously
→ WhatsApp reminders work through queues
```

That is the true production-grade foundation.
