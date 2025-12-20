# Authentication Fix Summary

## Problem
- Getting "not authenticated" error even with valid token
- "Attempt to read on null" when removing auth:sanctum middleware

## Root Cause
Sanctum's `PersonalAccessToken` model wasn't using the tenant database connection, so tokens couldn't be found.

## Fixes Applied

### 1. Updated PersonalAccessToken Model
**File:** `app/Models/PersonalAccessToken.php`

- Now extends `Laravel\Sanctum\PersonalAccessToken`
- Uses `tenant` connection
- Dynamically checks if tenant database is set

### 2. Configured Sanctum to Use Custom Model
**File:** `app/Providers/AppServiceProvider.php`

- Added `Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class)`
- Tells Sanctum to use our custom model

### 3. Updated ResolveTenant Middleware
**File:** `app/Http/Middleware/ResolveTenant.php`

- Explicitly calls `$tenant->makeCurrent()` to ensure database connection is switched
- This happens BEFORE Sanctum tries to authenticate

### 4. Added Error Handling
**File:** `app/Http/Controllers/Api/TenantAuthController.php`

- Added null check in `me()` method
- Returns proper 401 error if user is not authenticated

## How It Works Now

```
Request → ResolveTenant Middleware
  ↓
1. Finds tenant by subdomain
2. Calls $tenant->makeCurrent()
3. Switches database connection to tenant database
  ↓
Sanctum Middleware
  ↓
1. Looks for token in Authorization header
2. Queries personal_access_tokens table in TENANT database
3. Finds token and authenticates user
  ↓
Controller
  ↓
Returns user data
```

## Testing

### Step 1: Login
```bash
POST http://said.thruoo.local:8000/api/auth/login
Content-Type: application/json

{
  "email": "your-email@said.com",
  "password": "your-password"
}
```

**Copy the token from response!**

### Step 2: Get User Info
```bash
GET http://said.thruoo.local:8000/api/auth/me
Authorization: Bearer YOUR_TOKEN_HERE
```

**Important:** 
- Use exact subdomain: `said.thruoo.local:8000`
- Include `Bearer ` prefix (with space)
- Use the exact token from login

## If Still Not Working

1. **Clear config cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Verify token exists in tenant database:**
   ```bash
   php artisan tinker
   ```
   Then:
   ```php
   // Switch to tenant database
   Config::set('database.connections.tenant.database', 'tenant_said');
   DB::purge('tenant');
   DB::reconnect('tenant');
   
   // Check tokens
   DB::connection('tenant')->table('personal_access_tokens')->get();
   ```

3. **Check if user exists:**
   ```php
   DB::connection('tenant')->table('users')->get();
   ```

4. **Verify tenant exists:**
   ```php
   \App\Models\Landlord\Tenant::where('subdomain', 'said')->first();
   ```

## Common Issues

### "Tenant not found"
- Check hosts file has `said.thruoo.local`
- Verify tenant exists in landlord database
- Check subdomain spelling

### "Not authenticated"
- Verify token is sent in Authorization header
- Check format: `Bearer TOKEN` (with space)
- Verify token exists in tenant's personal_access_tokens table
- Make sure you're using the correct subdomain

### "Attempt to read on null"
- This means `$request->user()` is null
- Token is not being authenticated
- Check token format and database connection

