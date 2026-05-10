# Laravel Multi-tenant Offline Sync Architecture (Simplified MVP)

## Scope

This architecture is designed for an offline-first Laravel application where:

* A user registers their shop/business
* A shop owner can manage customers
* A shop owner can manage customer ledger transactions
* A shop owner can record received payments
* Mobile/web apps continue working without internet
* Data syncs automatically once internet is restored
* Strict tenant isolation is enforced
* Sync must be idempotent and safe under retries
* Redis is used for queues, cache, rate limiting, and sync state

This is intentionally simplified for MVP.

Removed from current scope:

* Orders
* Order items
* Inventory
* Complex workflow engines
* Realtime collaboration
* Advanced field-level merge systems

---

# 1) System Architecture Diagram

```text
[Client App (Mobile/Web)]
  ├─ local database
  ├─ offline operation queue
  ├─ auth state
  └─ sync worker
      ├─ POST /api/v1/sync/push
      ├─ GET  /api/v1/sync/pull
      └─ POST /api/v1/auth/refresh

[Laravel API]
  ├─ Sanctum Auth Middleware
  ├─ Tenant Resolver Middleware
  ├─ Session Validator Middleware
  ├─ Idempotency Middleware
  └─ Dispatch Sync Jobs

[Redis]
  ├─ queues:*
  ├─ idem:{tenant}:{request_id}
  ├─ ratelimit:*
  ├─ cache:tenant:{id}:*
  ├─ sync_state:{tenant}:{device}
  └─ refresh_lock:{session}

[Horizon Workers]
  ├─ ProcessSyncBatchJob
  ├─ AuditLogJob
  └─ Cache Invalidation Jobs

[Database]
  ├─ tenants
  ├─ users
  ├─ tenant_users
  ├─ devices
  ├─ customers
  ├─ ledger_transactions
  ├─ payment_receipts
  ├─ auth_sessions
  ├─ sync_logs
  ├─ sync_operations
  ├─ sync_cursors
  ├─ idempotency_keys
  └─ audit_logs
```

---

# 2) Business Flow

## Shop Registration

* User creates account
* User creates a shop/business
* Shop becomes a tenant
* User becomes owner of tenant

## Customer Management

* Shop owner creates customers
* Customers are stored locally first
* Sync worker uploads changes later

## Ledger Transactions

Examples:

* Customer purchased items
* Customer balance increased
* Adjustment entry
* Credit/Debit ledger records

## Payment Receipts

Examples:

* Customer paid cash
* Customer paid partial amount
* Customer balance reduced

---

# 3) Offline Sync Flow

## Client Offline

User actions are stored locally:

```text
create customer
update customer
create ledger transaction
create payment receipt
```

Each operation includes:

* operation_id
* entity_uuid
* operation type
* timestamp
* client version

## Internet Restored

Client sends batch:

```text
POST /api/v1/sync/push
```

## API Processing

Server:

1. validates auth
2. validates tenant
3. validates idempotency
4. creates sync_log
5. dispatches queue job
6. returns 202 accepted

## Queue Worker

Worker:

* processes operations safely
* prevents duplicates
* applies DB writes
* increments server versions
* records conflicts/errors

## Pull Delta Sync

Client requests:

```text
GET /api/v1/sync/pull?cursor=123
```

Server returns all changes after cursor.

---

# 4) Multi-tenant Isolation Rules

## Mandatory Rules

Every tenant-owned table must contain:

```php
tenant_id
```

All queries must automatically scope by tenant.

## Tenant Trait

```php
trait BelongsToTenant
{
    protected static function booted()
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        static::creating(function ($model) {
            $model->tenant_id = tenant()->id;
        });
    }
}
```

## Security Rule

Never use raw unscoped queries in domain logic.

Bad:

```php
Customer::query()->get();
```

Good:

```php
Customer::query()->whereTenantId(tenant()->id)->get();
```

---

# 5) Database Schema

# tenants

```text
id ULID PK
name
slug
status
plan
created_at
updated_at
```

Indexes:

* unique(slug)
* index(status)

---

# users

```text
id ULID PK
email
password_hash
status
created_at
updated_at
```

Indexes:

* unique(email)
* index(status)

---

# tenant_users

```text
tenant_id
user_id
role
status
joined_at
```

Indexes:

* PK(tenant_id,user_id)
* index(user_id)

---

# devices

```text
id ULID PK
tenant_id
user_id
device_uuid
platform
last_seen_at
created_at
updated_at
```

Indexes:

* unique(tenant_id,device_uuid)
* index(tenant_id,last_seen_at)

---

# customers

```text
id ULID PK
tenant_id
entity_uuid
name
phone
email
address
notes
current_balance_cents
server_version BIGINT
deleted_at
created_at
updated_at
```

Indexes:

* unique(tenant_id,entity_uuid)
* index(tenant_id,phone)
* index(tenant_id,updated_at)

---

# ledger_transactions

```text
id ULID PK
tenant_id
entity_uuid
customer_id
transaction_type
amount_cents
balance_after_cents
description
transaction_date
server_version BIGINT
deleted_at
created_at
updated_at
```

Transaction Types:

* debit
* credit
* adjustment

Indexes:

* unique(tenant_id,entity_uuid)
* index(tenant_id,customer_id)
* index(tenant_id,transaction_date)

---

# payment_receipts

```text
id ULID PK
tenant_id
entity_uuid
customer_id
amount_cents
payment_method
reference_number
notes
received_at
server_version BIGINT
deleted_at
created_at
updated_at
```

Indexes:

* unique(tenant_id,entity_uuid)
* index(tenant_id,customer_id)
* index(tenant_id,received_at)

---

# sync_logs

```text
id ULID PK
tenant_id
device_id
request_id
direction
status
received_at
finished_at
error_json
created_at
updated_at
```

Indexes:

* unique(tenant_id,request_id)
* index(tenant_id,device_id,received_at)

---

# sync_operations

```text
id ULID PK
tenant_id
sync_log_id
operation_id
entity_type
entity_uuid
operation
client_ts
status
conflict_json
created_at
updated_at
```

Indexes:

* unique(tenant_id,operation_id)
* index(tenant_id,entity_type,entity_uuid)

---

# sync_cursors

```text
tenant_id
device_id
last_pulled_version
last_pushed_at
created_at
updated_at
```

Indexes:

* PK(tenant_id,device_id)

---

# idempotency_keys

```text
id ULID PK
tenant_id
request_id
request_hash
response_json
status
expires_at
created_at
updated_at
```

Indexes:

* unique(tenant_id,request_id)
* index(expires_at)

---

# audit_logs

```text
id ULID PK
tenant_id
actor_user_id
entity_type
entity_id
action
before_json
after_json
created_at
```

Indexes:

* index(tenant_id,entity_type,entity_id)
* index(tenant_id,created_at)

---

# 6) Sync API

# POST /api/v1/sync/push

```json
{
  "request_id": "01J...",
  "device_id": "01J...",
  "operations": [
    {
      "operation_id": "01J...",
      "operation": "create",
      "entity_type": "customer",
      "entity_uuid": "cust_001",
      "client_version": 1,
      "payload": {}
    }
  ]
}
```

Response:

```json
{
  "ok": true,
  "code": "SYNC_ACCEPTED",
  "data": {
    "sync_log_id": "01J...",
    "accepted_operations": 10
  }
}
```

---

# GET /api/v1/sync/pull

```text
GET /api/v1/sync/pull?cursor=1200&limit=500
```

Response:

```json
{
  "ok": true,
  "code": "SYNC_DELTA",
  "data": {
    "cursor": 1300,
    "has_more": false,
    "changes": []
  }
}
```

---

# GET /api/v1/sync/status/{request_id}

Returns:

* processing
* completed
* failed
* partial_failed

---

# 7) Idempotency Design

## Request Level

Redis key:

```text
idem:{tenant_id}:{request_id}
```

TTL:

```text
24 hours
```

Duplicate request:

* same payload -> return existing response
* different payload -> reject with conflict

## Operation Level

Prevent duplicate operations using:

```text
unique(tenant_id, operation_id)
```

---

# 8) Conflict Resolution

Current strategy:

```text
Server Authoritative
```

Rules:

* stale updates rejected
* deleted rows reject stale updates
* newer server versions win

Conflict example:

```json
{
  "operation_id": "01J...",
  "status": "conflict",
  "conflict_type": "VERSION_MISMATCH",
  "resolution_hint": "pull_latest_then_reapply"
}
```

---

# 9) Redis Usage

## Cache

```text
cache:tenant:{tenant}:customer:{id}
```

TTL:

```text
5 minutes
```

## Idempotency

```text
idem:{tenant}:{request_id}
```

## Sync State

```text
sync_state:{tenant}:{device}
```

## Queue Backend

```text
queues:sync
queues:default
```

---

# 10) Queue System

## Queues

### sync-high

Handles:

* ProcessSyncBatchJob

Retries:

```text
tries=10
backoff=1,5,15,30,60
```

---

# 11) Events and Listeners

## Events

* CustomerCreated
* CustomerUpdated
* CustomerDeleted
* LedgerTransactionCreated
* PaymentReceiptCreated

## Listeners

* InvalidateTenantCacheListener
* AppendChangeFeedListener
* AuditLogListener

---

# 12) Authentication

Use:

```text
Laravel Sanctum
```

Abilities:

```text
sync:push
sync:pull
customer:read
customer:write
ledger:read
ledger:write
```

---

# 13) Rate Limits

## Sync Push

```text
120 req/min per tenant
30 req/min per user
```

## Sync Pull

```text
240 req/min per tenant
60 req/min per user
```

Return:

```text
429 Too Many Requests
```

---

# 14) Laravel Folder Structure

```text
app/
  Domain/
    Customer/
    Ledger/
    Payment/
    Sync/
    Tenant/

  Http/
    Controllers/Api/V1/Sync/
    Middleware/
    Requests/

  Models/
    Tenant
    User
    Customer
    LedgerTransaction
    PaymentReceipt
    SyncLog
    SyncOperation
    SyncCursor

  Support/
    Cache/
    Idempotency/
    ApiResponse/
```

---

# 15) Important Engineering Rules

## Use ULIDs

Never expose incremental IDs publicly.

---

## Use Soft Deletes

Never hard delete synced entities.

---

## Increment server_version

Every successful mutation increments:

```text
server_version
```

---

## Avoid Giant Sync Batches

Recommended:

```text
max 100-500 operations per batch
```

---

## Always Use Database Transactions

Critical for:

* ledger consistency
* balance updates
* sync integrity

---

## Never Trust Client State

Client data is eventually consistent.
Server remains source of truth.

---

# 16) MVP Summary

This MVP architecture provides:

* offline-first sync
* tenant isolation
* customer management
* ledger system
* payment receipt tracking
* Redis-backed infrastructure
* queue-based sync processing
* idempotent retries
* conflict detection
* secure sync APIs
* scalable Laravel structure

while intentionally avoiding unnecessary enterprise complexity in the first version.
