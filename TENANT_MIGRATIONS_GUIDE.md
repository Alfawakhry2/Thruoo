# 🔄 TENANT MIGRATIONS GUIDE

## 📋 Overview

When you add new migrations to the `database/migrations/tenant/` folder, you need to run them on ALL existing tenant databases, not just new ones.

---

## ✅ **Solution: Custom Artisan Commands**

I've created 3 commands to help you manage tenant migrations:

### **Commands Created:**

1. **`tenants:migrate`** - Migrate all tenants
2. **`tenant:migrate {subdomain}`** - Migrate specific tenant
3. **`tenants:list`** - List all tenants

---

## 🚀 **USAGE**

### **1. Migrate All Tenants**

Run migrations on ALL active tenants:

```bash
php artisan tenants:migrate
```

**With options:**

```bash
# Fresh migration (drop all tables and re-run)
php artisan tenants:migrate --fresh

# Run migrations + seeders
php artisan tenants:migrate --seed

# Fresh migration + seeders
php artisan tenants:migrate --fresh --seed
```

**Output Example:**
```
Found 3 active tenant(s)

Processing tenant: Test Company (testcompany)
✅ Migration completed for: Test Company

Processing tenant: ABC Corp (abccorp)
✅ Migration completed for: ABC Corp

Processing tenant: XYZ Ltd (xyzltd)
✅ Migration completed for: XYZ Ltd

=================================
Migration Summary:
Total tenants: 3
✅ Successful: 3
=================================
```

---

### **2. Migrate Specific Tenant**

Run migrations on ONE specific tenant:

```bash
php artisan tenant:migrate testcompany
```

**With options:**

```bash
# Fresh migration
php artisan tenant:migrate testcompany --fresh

# With seeder
php artisan tenant:migrate testcompany --seed

# Both
php artisan tenant:migrate testcompany --fresh --seed
```

**Output Example:**
```
Found tenant: Test Company (testcompany)
Status: active

Running migrations...
✅ Migration completed successfully!
```

---

### **3. List All Tenants**

View all tenants:

```bash
php artisan tenants:list
```

**Filter by status:**

```bash
# Only active tenants
php artisan tenants:list --status=active

# Only inactive tenants
php artisan tenants:list --status=inactive
```

**Output Example:**
```
+----+---------------+-------------+--------+------+------------------+
| ID | Name          | Subdomain   | Status | Plan | Created At       |
+----+---------------+-------------+--------+------+------------------+
| 1  | Test Company  | testcompany | active | pro  | 2025-12-20 10:00 |
| 2  | ABC Corp      | abccorp     | active | free | 2025-12-19 15:30 |
| 3  | XYZ Ltd       | xyzltd      | active | pro  | 2025-12-18 09:15 |
+----+---------------+-------------+--------+------+------------------+

Total tenants: 3
```

---

## 📝 **TYPICAL WORKFLOW**

### **When You Add New Migrations:**

**Step 1:** Create your migration
```bash
# Migration is already created in database/migrations/tenant/
```

**Step 2:** Test on one tenant first
```bash
php artisan tenant:migrate testcompany
```

**Step 3:** If successful, migrate all tenants
```bash
php artisan tenants:migrate
```

**Step 4:** Verify
```bash
# Check if migration ran on all tenants
php artisan tenants:list
```

---

## 🎯 **COMMON SCENARIOS**

### **Scenario 1: New Tenant Registration**

When a new tenant registers, migrations run automatically during registration process. No action needed!

### **Scenario 2: Adding New Feature (Like Leads)**

You just added the Leads system with 4 new migrations:

```bash
# 1. List all tenants to see how many you have
php artisan tenants:list

# 2. Test on one tenant first
php artisan tenant:migrate testcompany

# 3. If successful, migrate all
php artisan tenants:migrate

# 4. Optional: Seed default data
php artisan tenant:migrate testcompany --seed
```

### **Scenario 3: Fresh Start (Development)**

Reset everything for all tenants:

```bash
# WARNING: This will DELETE all data!
php artisan tenants:migrate --fresh --seed
```

### **Scenario 4: Rollback (if needed)**

Unfortunately, there's no built-in rollback for all tenants yet, but you can create one if needed.

---

## 🔧 **HOW IT WORKS**

The `tenants:migrate` command:

1. Connects to **landlord database** (mysql)
2. Gets all **active tenants**
3. For each tenant:
   - Switches connection to that tenant's database
   - Runs migrations from `database/migrations/tenant/`
   - Switches back to landlord
4. Shows summary

---

## ⚠️ **IMPORTANT NOTES**

### **Database Connections:**

- **Landlord DB:** `mysql` - stores tenant information
- **Tenant DBs:** `tenant_{subdomain}` - individual tenant databases

### **Migration Paths:**

- **Landlord migrations:** `database/migrations/landlord/`
- **Tenant migrations:** `database/migrations/tenant/`
- **Shared migrations:** `database/migrations/` (users, cache, jobs - also need to be in tenant/)

### **Status Filter:**

Only **active** tenants are migrated by default. If you want to include all tenants regardless of status, modify the command.

---

## 🛠️ **ADVANCED: Custom Migration Script**

If you need more control, you can create a custom migration script:

```php
<?php
// migrate-tenants.php

use App\Models\Landlord\Tenant;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenants = Tenant::on('mysql')->where('status', 'active')->get();

foreach ($tenants as $tenant) {
    echo "Migrating: {$tenant->name}\n";
    
    $tenant->makeCurrent();
    
    Artisan::call('migrate', [
        '--path' => 'database/migrations/tenant',
        '--force' => true,
    ]);
    
    echo "✅ Done\n\n";
}

Tenant::forgetCurrent();
echo "All tenants migrated!\n";
```

Run with:
```bash
php migrate-tenants.php
```

---

## 📊 **MONITORING**

You can check which migrations have been run on each tenant:

```bash
# Connect to specific tenant database
mysql -u root -p tenant_testcompany

# Check migrations table
SELECT * FROM migrations ORDER BY id DESC;
```

---

## ✅ **BEST PRACTICES**

1. **Always test on one tenant first** before migrating all
2. **Backup databases** before running `--fresh`
3. **Use `--seed`** only when you want to add default data
4. **Monitor the output** to catch any errors
5. **Keep migrations atomic** - one change per migration file
6. **Name migrations descriptively** - e.g., `2025_12_20_100001_create_modules_table.php`

---

## 🎉 **QUICK REFERENCE**

```bash
# List all tenants
php artisan tenants:list

# Migrate all tenants
php artisan tenants:migrate

# Migrate specific tenant
php artisan tenant:migrate testcompany

# Fresh migration (all tenants)
php artisan tenants:migrate --fresh

# Fresh migration + seed (all tenants)
php artisan tenants:migrate --fresh --seed

# Specific tenant with seed
php artisan tenant:migrate testcompany --seed
```

---

## 🆘 **TROUBLESHOOTING**

### **Error: "No active tenants found!"**
- Check if you have any tenants: `php artisan tenants:list`
- Make sure tenants have `status = 'active'`

### **Error: "Migration failed for tenant X"**
- Check the error message
- Test that specific tenant: `php artisan tenant:migrate X`
- Check tenant database exists
- Check database credentials

### **Migration ran on some tenants but not all?**
- Re-run `php artisan tenants:migrate` - it will skip already-run migrations
- Check failed tenants individually

---

**Now you can easily migrate all your tenants with one command!** 🚀
