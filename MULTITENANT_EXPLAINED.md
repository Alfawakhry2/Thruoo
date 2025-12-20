# Multi-Tenant System - Detailed Explanation

## Table of Contents
1. [How Multi-Tenancy Works](#how-multi-tenancy-works)
2. [Tenant Registration Flow](#tenant-registration-flow)
3. [Trial & Subscription System](#trial--subscription-system)
4. [Request Flow Diagram](#request-flow-diagram)
5. [Database Isolation](#database-isolation)

---

## How Multi-Tenancy Works

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Landlord Database                        │
│                  (thruoo_landlord)                          │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ tenants table                                        │  │
│  │ - id (uuid)                                          │  │
│  │ - subdomain (unique)                                 │  │
│  │ - database (tenant_acme)                             │  │
│  │ - status (active/suspended/cancelled)                │  │
│  │ - trial_ends_at                                      │  │
│  │ - enabled_modules (JSON)                             │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
        ▼                   ▼                   ▼
┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│ Tenant DB 1  │   │ Tenant DB 2  │   │ Tenant DB 3  │
│ tenant_acme  │   │ tenant_demo  │   │ tenant_test  │
│              │   │              │   │              │
│ - users      │   │ - users      │   │ - users      │
│ - leads      │   │ - leads      │   │ - leads      │
│ - deals      │   │ - deals      │   │ - deals      │
│ - roles      │   │ - roles      │   │ - roles      │
│ - permissions│   │ - permissions│   │ - permissions│
└──────────────┘   └──────────────┘   └──────────────┘
```

### Key Components

1. **Landlord Database** (`thruoo_landlord`)
   - Stores tenant metadata
   - One database for all tenant information
   - Contains: `tenants` table

2. **Tenant Databases** (`tenant_{subdomain}`)
   - Each tenant has its own isolated database
   - Complete data isolation
   - Contains: users, leads, deals, roles, permissions, etc.

3. **Tenant Resolution**
   - Based on subdomain (e.g., `acme.thruoo.local`)
   - Extracted from HTTP `Host` header
   - Resolved by `SubdomainTenantFinder`

---

## Tenant Registration Flow

### Step-by-Step Process

When you call `POST /api/tenants/register`, here's what happens:

```
1. Request Received
   ↓
2. Validation (subdomain uniqueness, email format, etc.)
   ↓
3. Create Tenant Database
   ↓
4. Create Tenant Record (in landlord DB)
   ↓
5. Run Tenant Migrations
   ↓
6. Create Admin User
   ↓
7. Seed Roles & Permissions
   ↓
8. Return Success Response
```

### Detailed Breakdown

#### Step 1: Request Validation
**File:** `app/Http/Controllers/Api/TenantRegistrationController.php`

```php
// Validates:
- company_name: required
- subdomain: required, unique, alpha_dash
- email: required, valid email
- password: required, min 8 chars, confirmed
- name: required
- modules: optional array (sales, contacts, accounting)
```

#### Step 2: Create Tenant Database
**File:** `app/Services/Tenant/CreateTenantService.php::createTenantDatabase()`

```php
// Creates MySQL database:
CREATE DATABASE `tenant_acme` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci

// Database name format: tenant_{subdomain}
// Example: tenant_acme, tenant_demo
```

**Why separate?** DDL statements (CREATE DATABASE) cannot run inside transactions in MySQL.

#### Step 3: Create Tenant Record
**File:** `app/Services/Tenant/CreateTenantService.php::create()`

```php
// Inserts into landlord database (thruoo_landlord.tenants):
{
    "id": "uuid-here",
    "name": "Acme Corp",
    "subdomain": "acme",
    "database": "tenant_acme",
    "email": "admin@acme.com",
    "status": "active",
    "trial_ends_at": "2025-12-14 16:00:00", // 7 days from now
    "plan": "free",
    "enabled_modules": ["sales"]
}
```

**Transaction:** This is committed immediately to ensure tenant record exists.

#### Step 4: Run Tenant Migrations
**File:** `app/Services/Tenant/CreateTenantService.php::runTenantMigrations()`

```php
// Switches to tenant database connection
Config::set('database.connections.tenant.database', 'tenant_acme');

// Runs all migrations in database/migrations/tenant/:
1. 0001_01_01_000001_create_cache_table.php
2. 0001_01_01_000002_create_jobs_table.php
3. 2025_12_07_142408_create_permission_tables.php
4. 2025_12_07_142555_create_personal_access_tokens_table.php
5. 2025_12_07_160029_create_users_table.php (includes sessions)
6. 2025_12_07_160052_create_leads_table.php
7. 2025_12_07_160102_create_deals_table.php
```

**Result:** Complete database schema for the tenant.

#### Step 5: Create Admin User
**File:** `app/Services/Tenant/CreateTenantService.php::createAdminUser()`

```php
// Inserts into tenant database (tenant_acme.users):
{
    "id": 1,
    "name": "John Doe",
    "email": "admin@acme.com",
    "password": "$2y$10$...", // Hashed
    "phone": "+1234567890"
}

// Assigns "Super Admin" role
```

#### Step 6: Seed Roles & Permissions
**File:** `app/Services/Tenant/CreateTenantService.php::seedRolesAndPermissions()`

```php
// Creates permissions:
- view_leads, create_leads, edit_leads, delete_leads
- view_deals, create_deals, edit_deals, delete_deals
- view_reports, manage_users, manage_settings

// Creates roles:
1. Super Admin (all permissions)
2. Sales Manager (manage leads/deals, view reports)
3. Sales Representative (create/edit own leads)
4. User (basic access)

// Assigns Super Admin role to the created user
```

---

## Request Flow Diagram

### When User Accesses `acme.thruoo.local:8000/api/auth/login`

```
┌─────────────────────────────────────────────────────────────┐
│ 1. HTTP Request Arrives                                     │
│    Host: acme.thruoo.local:8000                             │
│    Path: /api/auth/login                                    │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. SubdomainTenantFinder                                    │
│    - Extracts subdomain: "acme"                             │
│    - Queries landlord DB:                                   │
│      SELECT * FROM tenants                                  │
│      WHERE subdomain = 'acme'                               │
│      AND status = 'active'                                  │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. ResolveTenant Middleware                                 │
│    - Checks tenant exists                                   │
│    - Checks status = 'active'                               │
│    - Calls: $tenant->makeCurrent()                          │
│      ↓                                                      │
│    - Switches DB connection to 'tenant'                     │
│    - Sets database name to 'tenant_acme'                    │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. EnsureSubscriptionActive Middleware                      │
│    - Checks: $tenant->isActive()                            │
│    - Validates trial/subscription status                    │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. TenantAuthController@login                               │
│    - Queries tenant database (tenant_acme.users)            │
│    - Authenticates user                                     │
│    - Returns token                                          │
└─────────────────────────────────────────────────────────────┘
```

### Database Connection Switching

```php
// Before tenant resolution:
DB::connection() // Uses 'mysql' (landlord)

// After tenant resolution:
DB::connection() // Uses 'tenant' (tenant_acme)

// All subsequent queries go to tenant database:
User::where('email', 'admin@acme.com')->first();
// Queries: tenant_acme.users table
```

---

## Trial & Subscription System

### How It Works

**File:** `app/Models/Landlord/Tenant.php`

### Trial Status Methods

#### 1. `isOnTrial()`
```php
public function isOnTrial(): bool
{
    return $this->trial_ends_at && $this->trial_ends_at->isFuture();
}
```
**Checks:** Is `trial_ends_at` in the future?

**Example:**
- `trial_ends_at`: `2025-12-14 16:00:00`
- Current time: `2025-12-10 10:00:00`
- Result: `true` ✅

#### 2. `trialExpired()`
```php
public function trialExpired(): bool
{
    if (!$this->trial_ends_at) {
        return false;
    }
    return $this->trial_ends_at->isPast();
}
```
**Checks:** Has `trial_ends_at` passed?

**Example:**
- `trial_ends_at`: `2025-12-14 16:00:00`
- Current time: `2025-12-15 10:00:00`
- Result: `true` (trial expired) ⚠️

#### 3. `isInGracePeriod()`
```php
public function isInGracePeriod(): bool
{
    if (!$this->trialExpired()) {
        return false;
    }
    return $this->trial_ends_at->copy()->addDays(3)->isFuture();
}
```
**Checks:** Is it within 3 days after trial expiration?

**Example:**
- `trial_ends_at`: `2025-12-14 16:00:00`
- Current time: `2025-12-16 10:00:00` (2 days after)
- Grace period ends: `2025-12-17 16:00:00`
- Result: `true` (still in grace period) ⚠️

#### 4. `isActive()`
```php
public function isActive(): bool
{
    return $this->status === 'active' && 
           ($this->isOnTrial() || 
            $this->isInGracePeriod() || 
            ($this->subscription_ends_at && $this->subscription_ends_at->isFuture()));
}
```
**Checks:** Is tenant fully active?

**Conditions:**
- Status must be `'active'`
- AND one of:
  - Trial is active, OR
  - In grace period, OR
  - Has active subscription

### Trial Timeline

```
Day 0: Registration
├─ trial_ends_at = now() + 7 days
└─ status = 'active'
   ✅ Full access

Day 1-7: Active Trial
├─ isOnTrial() = true
└─ ✅ Full access

Day 8: Trial Expired
├─ isOnTrial() = false
├─ trialExpired() = true
├─ isInGracePeriod() = true (Day 8-10)
└─ ⚠️ Still has access (grace period)

Day 11: Grace Period Expired
├─ isInGracePeriod() = false
├─ isActive() = false
└─ ❌ Access denied (unless subscription active)
```

### Subscription Checking

**File:** `app/Http/Middleware/EnsureSubscriptionActive.php`

```php
public function handle(Request $request, Closure $next): Response
{
    $tenant = Tenant::current();
    
    if (!$tenant->isActive()) {
        return response()->json([
            'success' => false,
            'message' => 'Subscription has expired. Please renew your subscription.',
            'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
            'subscription_ends_at' => $tenant->subscription_ends_at?->toIso8601String(),
        ], 403);
    }
    
    return $next($request);
}
```

**Applied to:** All tenant routes (except registration)

**When checked:** On every request to tenant subdomain

### Module Access Checking

**File:** `app/Http/Middleware/EnsureModuleEnabled.php`

```php
public function handle(Request $request, Closure $next, string $module): Response
{
    $tenant = Tenant::current();
    
    if (!$tenant->hasModule($module)) {
        return response()->json([
            'success' => false,
            'message' => "Module '{$module}' is not enabled for this tenant",
        ], 403);
    }
    
    return $next($request);
}
```

**Usage in routes:**
```php
Route::middleware(['ensure.module:sales'])->prefix('sales')->group(function () {
    Route::apiResource('leads', LeadController::class);
});
```

**Checks:** `enabled_modules` JSON field in tenants table

---

## Database Isolation

### How Data is Isolated

#### Example: Two Tenants

**Tenant 1: Acme Corp**
- Subdomain: `acme`
- Database: `tenant_acme`
- Users: `admin@acme.com`, `user1@acme.com`
- Leads: Lead #1, Lead #2

**Tenant 2: Demo Corp**
- Subdomain: `demo`
- Database: `tenant_demo`
- Users: `admin@demo.com`, `user2@demo.com`
- Leads: Lead #1, Lead #2

**Important:** Even though both have "Lead #1", they are in completely separate databases!

### Query Examples

```php
// When accessing acme.thruoo.local:
User::count(); // Returns 2 (only Acme's users)
Lead::count(); // Returns 2 (only Acme's leads)

// When accessing demo.thruoo.local:
User::count(); // Returns 2 (only Demo's users)
Lead::count(); // Returns 2 (only Demo's leads)
```

**No cross-tenant data leakage possible!**

---

## Summary

### What Happens During Registration

1. ✅ Tenant database created (`tenant_acme`)
2. ✅ Tenant record created in landlord DB
3. ✅ All migrations run on tenant DB
4. ✅ Admin user created in tenant DB
5. ✅ Roles & permissions seeded
6. ✅ 7-day trial activated
7. ✅ Status set to 'active'

### How Requests Work

1. Request arrives with subdomain
2. Tenant found in landlord DB
3. Database connection switched to tenant DB
4. Trial/subscription checked
5. Module access checked
6. Request processed with tenant context

### Trial System

- **7 days** free trial
- **3 days** grace period after expiration
- Checked on **every request**
- Returns **403** if expired (unless in grace period)

---

## Testing the System

### Check Tenant Status
```php
$tenant = \App\Models\Landlord\Tenant::where('subdomain', 'acme')->first();

echo "Trial Active: " . ($tenant->isOnTrial() ? 'Yes' : 'No') . "\n";
echo "Trial Expired: " . ($tenant->trialExpired() ? 'Yes' : 'No') . "\n";
echo "In Grace Period: " . ($tenant->isInGracePeriod() ? 'Yes' : 'No') . "\n";
echo "Is Active: " . ($tenant->isActive() ? 'Yes' : 'No') . "\n";
```

### Manually Extend Trial (for testing)
```php
$tenant = \App\Models\Landlord\Tenant::where('subdomain', 'acme')->first();
$tenant->trial_ends_at = now()->addDays(30);
$tenant->save();
```

### Check Module Access
```php
$tenant = \App\Models\Landlord\Tenant::where('subdomain', 'acme')->first();
echo "Has Sales Module: " . ($tenant->hasModule('sales') ? 'Yes' : 'No') . "\n";
```

---

This system ensures complete data isolation while providing flexible subscription and module management! 🚀

