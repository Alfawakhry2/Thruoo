# Local Testing Guide - Step by Step

This guide will walk you through testing the Thruoo CRM multi-tenant system locally.

## Prerequisites

- PHP 8.2+
- MySQL/MariaDB
- Composer
- MySQL user with CREATE DATABASE privileges

---

## Step 1: Verify Environment Setup

### 1.1 Check PHP Version
```bash
php -v
```
Should show PHP 8.2 or higher.

### 1.2 Check Composer
```bash
composer --version
```

### 1.3 Check MySQL
```bash
mysql --version
```

---

## Step 2: Configure Environment File

### 2.1 Create .env file (if not exists)
```bash
# Windows
copy .env.example .env

# Linux/Mac
cp .env.example .env
```

### 2.2 Edit .env file
Open `.env` and set these values:

```env
APP_NAME=Thruoo
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://thruoo.local
TENANT_DOMAIN=thruoo.local

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thruoo_landlord
DB_USERNAME=root
DB_PASSWORD=your_mysql_password_here
```

**Important:** Replace `your_mysql_password_here` with your actual MySQL root password (or leave empty if no password).

### 2.3 Generate Application Key
```bash
php artisan key:generate
```

---

## Step 3: Create Landlord Database

### 3.1 Connect to MySQL
```bash
mysql -u root -p
```

### 3.2 Create Database
```sql
CREATE DATABASE IF NOT EXISTS thruoo_landlord CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

---

## Step 4: Run Landlord Migrations

```bash
php artisan migrate --path=database/migrations/landlord
```

**Expected Output:**
```
INFO  Running migrations.

  2025_12_07_155839_create_tenants_table ................................................... DONE
```

Verify the tenants table was created:
```bash
php artisan tinker
```
Then in tinker:
```php
DB::connection('mysql')->table('tenants')->count();
// Should return: 0
exit
```

---

## Step 5: Configure Hosts File

### Windows

1. Open Notepad as Administrator
2. Open file: `C:\Windows\System32\drivers\etc\hosts`
3. Add these lines at the end:
```
127.0.0.1 thruoo.local
127.0.0.1 demo.thruoo.local
127.0.0.1 test.thruoo.local
127.0.0.1 acme.thruoo.local
```
4. Save the file

### Linux/Mac

```bash
sudo nano /etc/hosts
```

Add these lines:
```
127.0.0.1 thruoo.local
127.0.0.1 demo.thruoo.local
127.0.0.1 test.thruoo.local
127.0.0.1 acme.thruoo.local
```

Save (Ctrl+X, then Y, then Enter)

### Verify Hosts File
```bash
# Windows
ping thruoo.local

# Linux/Mac
ping -c 1 thruoo.local
```

Should resolve to `127.0.0.1`

---

## Step 6: Start Laravel Development Server

```bash
php artisan serve --host=thruoo.local --port=8000
```

**Expected Output:**
```
INFO  Server running on [http://thruoo.local:8000]
```

**Keep this terminal window open!**

---

## Step 7: Test Tenant Registration

### 7.1 Register First Tenant

Open a **new terminal window** and run:

```bash
curl -X POST http://thruoo.local:8000/api/tenants/register ^
  -H "Content-Type: application/json" ^
  -d "{\"company_name\":\"Acme Corp\",\"subdomain\":\"acme\",\"email\":\"admin@acme.com\",\"password\":\"password123\",\"password_confirmation\":\"password123\",\"name\":\"John Doe\",\"phone\":\"+1234567890\",\"modules\":[\"sales\"]}"
```

**Linux/Mac:**
```bash
curl -X POST http://thruoo.local:8000/api/tenants/register \
  -H "Content-Type: application/json" \
  -d '{"company_name":"Acme Corp","subdomain":"acme","email":"admin@acme.com","password":"password123","password_confirmation":"password123","name":"John Doe","phone":"+1234567890","modules":["sales"]}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Tenant registered successfully",
  "data": {
    "tenant_id": "uuid-here",
    "subdomain": "acme",
    "tenant_url": "http://acme.thruoo.local",
    "trial_ends_at": "2025-12-14T16:00:00Z",
    "status": "active"
  }
}
```

### 7.2 Verify Database Creation

Check if tenant database was created:
```bash
mysql -u root -p -e "SHOW DATABASES LIKE 'tenant_%';"
```

You should see `tenant_acme` database.

### 7.3 Verify Tenant Record

```bash
php artisan tinker
```

```php
\App\Models\Landlord\Tenant::all();
// Should show your tenant
exit
```

---

## Step 8: Test Tenant Login

### 8.1 Login to Tenant

**Windows:**
```bash
curl -X POST http://acme.thruoo.local:8000/api/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"admin@acme.com\",\"password\":\"password123\"}"
```

**Linux/Mac:**
```bash
curl -X POST http://acme.thruoo.local:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@acme.com","password":"password123"}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "admin@acme.com",
      "phone": "+1234567890",
      "roles": ["Super Admin"],
      "permissions": ["view_leads", "create_leads", ...]
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
  }
}
```

**⚠️ IMPORTANT:** Copy the `token` value from the response. You'll need it for authenticated requests.

---

## Step 9: Test Sales Module - Create Lead

Replace `YOUR_TOKEN_HERE` with the token from Step 8.

### 9.1 Create a Lead

**Windows:**
```bash
curl -X POST http://acme.thruoo.local:8000/api/sales/leads ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE" ^
  -H "Content-Type: application/json" ^
  -d "{\"name\":\"Jane Smith\",\"email\":\"jane@example.com\",\"phone\":\"+1234567890\",\"company\":\"Example Corp\",\"status\":\"new\",\"value\":5000.00}"
```

**Linux/Mac:**
```bash
curl -X POST http://acme.thruoo.local:8000/api/sales/leads \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"name":"Jane Smith","email":"jane@example.com","phone":"+1234567890","company":"Example Corp","status":"new","value":5000.00}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Lead created successfully",
  "data": {
    "id": 1,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "phone": "+1234567890",
    "company": "Example Corp",
    "status": "new",
    "value": "5000.00",
    ...
  }
}
```

### 9.2 List Leads

**Windows:**
```bash
curl -X GET http://acme.thruoo.local:8000/api/sales/leads ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Linux/Mac:**
```bash
curl -X GET http://acme.thruoo.local:8000/api/sales/leads \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 9.3 Get Single Lead

**Windows:**
```bash
curl -X GET http://acme.thruoo.local:8000/api/sales/leads/1 ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Linux/Mac:**
```bash
curl -X GET http://acme.thruoo.local:8000/api/sales/leads/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Step 10: Test Sales Module - Create Deal

### 10.1 Create a Deal

**Windows:**
```bash
curl -X POST http://acme.thruoo.local:8000/api/sales/deals ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE" ^
  -H "Content-Type: application/json" ^
  -d "{\"title\":\"Website Redesign Project\",\"value\":15000.00,\"stage\":\"prospecting\",\"probability\":25,\"expected_close_date\":\"2025-12-31\",\"lead_id\":1}"
```

**Linux/Mac:**
```bash
curl -X POST http://acme.thruoo.local:8000/api/sales/deals \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"title":"Website Redesign Project","value":15000.00,"stage":"prospecting","probability":25,"expected_close_date":"2025-12-31","lead_id":1}'
```

### 10.2 List Deals

**Windows:**
```bash
curl -X GET http://acme.thruoo.local:8000/api/sales/deals ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Linux/Mac:**
```bash
curl -X GET http://acme.thruoo.local:8000/api/sales/deals \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Step 11: Test Get Current User

### 11.1 Get Authenticated User Info

**Windows:**
```bash
curl -X GET http://acme.thruoo.local:8000/api/auth/me ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Linux/Mac:**
```bash
curl -X GET http://acme.thruoo.local:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Step 12: Test Multiple Tenants

### 12.1 Register Second Tenant

**Windows:**
```bash
curl -X POST http://thruoo.local:8000/api/tenants/register ^
  -H "Content-Type: application/json" ^
  -d "{\"company_name\":\"Demo Company\",\"subdomain\":\"demo\",\"email\":\"admin@demo.com\",\"password\":\"password123\",\"password_confirmation\":\"password123\",\"name\":\"Demo Admin\",\"phone\":\"+9876543210\",\"modules\":[\"sales\"]}"
```

**Linux/Mac:**
```bash
curl -X POST http://thruoo.local:8000/api/tenants/register \
  -H "Content-Type: application/json" \
  -d '{"company_name":"Demo Company","subdomain":"demo","email":"admin@demo.com","password":"password123","password_confirmation":"password123","name":"Demo Admin","phone":"+9876543210","modules":["sales"]}'
```

### 12.2 Login to Second Tenant

**Windows:**
```bash
curl -X POST http://demo.thruoo.local:8000/api/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"admin@demo.com\",\"password\":\"password123\"}"
```

**Linux/Mac:**
```bash
curl -X POST http://demo.thruoo.local:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@demo.com","password":"password123"}'
```

### 12.3 Verify Data Isolation

Create a lead in the demo tenant, then check that it doesn't appear in the acme tenant's leads list. This proves multi-tenancy is working!

---

## Troubleshooting

### Issue: "Tenant not found" (404)

**Solution:**
1. Check hosts file is configured correctly
2. Verify tenant subdomain exists in database:
   ```bash
   php artisan tinker
   \App\Models\Landlord\Tenant::where('subdomain', 'acme')->first();
   ```
3. Ensure server is running on `thruoo.local:8000`

### Issue: "Database connection refused"

**Solution:**
1. Check MySQL is running: `mysql -u root -p`
2. Verify `.env` database credentials
3. Ensure landlord database exists: `SHOW DATABASES;`

### Issue: "SQLSTATE[HY000] [1045] Access denied"

**Solution:**
1. Check MySQL username/password in `.env`
2. Verify MySQL user has CREATE DATABASE privileges:
   ```sql
   GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost';
   FLUSH PRIVILEGES;
   ```

### Issue: "Class not found" errors

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue: CORS errors in browser

**Solution:**
The API is designed for server-to-server communication. Use curl, Postman, or similar tools. For browser testing, configure CORS in `config/cors.php`.

---

## Quick Test Script

Save this as `test-api.sh` (Linux/Mac) or `test-api.bat` (Windows):

**test-api.sh (Linux/Mac):**
```bash
#!/bin/bash

BASE_URL="http://thruoo.local:8000"
TENANT_URL="http://acme.thruoo.local:8000"

echo "1. Registering tenant..."
REGISTER_RESPONSE=$(curl -s -X POST $BASE_URL/api/tenants/register \
  -H "Content-Type: application/json" \
  -d '{"company_name":"Test Corp","subdomain":"acme","email":"admin@acme.com","password":"password123","password_confirmation":"password123","name":"Test Admin","modules":["sales"]}')

echo $REGISTER_RESPONSE | jq .

echo -e "\n2. Logging in..."
LOGIN_RESPONSE=$(curl -s -X POST $TENANT_URL/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@acme.com","password":"password123"}')

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.token')
echo "Token: $TOKEN"

echo -e "\n3. Creating lead..."
curl -s -X POST $TENANT_URL/api/sales/leads \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Lead","email":"lead@test.com","status":"new"}' | jq .

echo -e "\n4. Listing leads..."
curl -s -X GET $TENANT_URL/api/sales/leads \
  -H "Authorization: Bearer $TOKEN" | jq .
```

Make it executable:
```bash
chmod +x test-api.sh
./test-api.sh
```

---

## Next Steps

- Test update/delete operations
- Test permission-based access control
- Test subscription expiration scenarios
- Test module enable/disable
- Set up automated tests

---

## Success Checklist

- [ ] Landlord database created
- [ ] Landlord migrations run successfully
- [ ] Hosts file configured
- [ ] Server running on thruoo.local:8000
- [ ] Tenant registration works
- [ ] Tenant database created automatically
- [ ] Login works with subdomain
- [ ] Can create leads
- [ ] Can create deals
- [ ] Data is isolated between tenants

If all checkboxes are checked, your multi-tenant setup is working! 🎉

