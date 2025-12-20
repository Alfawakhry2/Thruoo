# Roles & Permissions System

## Overview

This document describes the roles and permissions structure for the Thruoo CRM multi-tenant system.

---

## Roles

### 1. Super Admin
**Description:** Full system access with all permissions.

**Permissions:** All permissions (automatically gets all current and future permissions)

**Use Case:** System administrators who need complete control.

---

### 2. Admin
**Description:** Tenant administrator with comprehensive access (everyone who registers gets this role).

**Permissions:**
- ✅ invoices
- ✅ purchasing_orders
- ✅ proposals
- ✅ targets
- ✅ item_prices
- ✅ payments

**Use Case:** Tenant owners/administrators who registered the company.

---

### 3. Assistant
**Description:** Limited access for assistants - can view and create but not edit/delete.

**Permissions:**
- ✅ invoices
- ✅ purchasing_orders
- ✅ proposals
- ✅ targets
- ✅ item_prices
- ✅ payments

**Use Case:** Administrative assistants who need to create records but not modify existing ones.

---

### 4. Sales
**Description:** Sales team members with sales-focused permissions.

**Permissions:**
- ✅ proposals
- ✅ targets
- ✅ invoices
- ❌ No access to purchasing_orders
- ❌ No access to payments
- ❌ No access to item_prices

**Use Case:** Sales representatives and sales managers.

---

### 5. Finance
**Description:** Finance team with financial operations access.

**Permissions:**
- ✅ invoices
- ✅ purchasing_orders
- ✅ payments
- ✅ item_prices
- ❌ No access to proposals
- ❌ No access to targets

**Use Case:** Finance team members, accountants, CFO.

---

## Permissions

### Simple Permission Structure (One Permission Per Resource)

- `invoices` - Full access to invoices (view, create, edit, delete)
- `purchasing_orders` - Full access to purchasing orders
- `proposals` - Full access to proposals
- `targets` - Full access to targets
- `item_prices` - Full access to item prices
- `payments` - Full access to payments

---

## Permission Matrix

| Permission | Super Admin | Admin | Assistant | Sales | Finance |
|------------|-------------|-------|-----------|-------|---------|
| invoices | ✅ | ✅ | ✅ | ✅ | ✅ |
| purchasing_orders | ✅ | ✅ | ✅ | ❌ | ✅ |
| proposals | ✅ | ✅ | ✅ | ✅ | ❌ |
| targets | ✅ | ✅ | ✅ | ✅ | ❌ |
| item_prices | ✅ | ✅ | ✅ | ❌ | ✅ |
| payments | ✅ | ✅ | ✅ | ❌ | ✅ |

---

## Implementation Details

### Automatic Role Assignment

When a tenant registers:
1. Tenant record is created
2. Roles and permissions are seeded
3. Admin user is created
4. **Admin role is automatically assigned** to the registering user

### Using Permissions in Code

#### Check Permission in Controller
```php
if (!auth()->user()->can('invoices')) {
    return response()->json(['message' => 'Unauthorized'], 403);
}
```

#### Check Permission in Blade (if using views)
```blade
@can('invoices')
    <button>Manage Invoices</button>
@endcan
```

#### Check Role
```php
if (auth()->user()->hasRole('Admin')) {
    // Admin user
}
```

#### Check Multiple Roles
```php
if (auth()->user()->hasAnyRole(['Admin', 'Finance'])) {
    // User is Admin or Finance
}
```

### Middleware Protection

You can protect routes with permissions:

```php
Route::middleware(['auth:sanctum', 'permission:invoices'])->group(function () {
    Route::apiResource('invoices', InvoiceController::class);
});
```

Or with roles:

```php
Route::middleware(['auth:sanctum', 'role:Admin|Finance'])->group(function () {
    Route::apiResource('invoices', InvoiceController::class);
});
```

---

## Adding New Permissions

To add new permissions:

1. Add permission to `$permissions` array in `CreateTenantService::seedRolesAndPermissions()`
2. Assign permissions to appropriate roles
3. Use permission checks in controllers/middleware

Example:
```php
// In CreateTenantService
$permissions = [
    // ... existing permissions
    'contracts',
];

// Assign to roles
$admin->givePermissionTo(['contracts']);
$sales->givePermissionTo(['contracts']);
```

---

## Testing Roles & Permissions

### Check User's Roles
```php
$user = User::find(1);
$user->getRoleNames(); // ['Admin']
```

### Check User's Permissions
```php
$user->getAllPermissions(); // Collection of all permissions
$user->getPermissionNames(); // ['invoices', 'purchasing_orders', 'proposals', ...]
```

### Assign Role to User
```php
$user->assignRole('Sales');
```

### Remove Role from User
```php
$user->removeRole('Sales');
```

### Give Permission Directly
```php
$user->givePermissionTo('invoices');
```

---

## Summary

- **5 Roles:** Super Admin, Admin, Assistant, Sales, Finance
- **6 Permissions:** invoices, purchasing_orders, proposals, targets, item_prices, payments
- **Automatic Assignment:** Admin role assigned to tenant registrant
- **Simple Structure:** One permission per resource (full access)
- **Secure:** Middleware and controller-level permission checks

