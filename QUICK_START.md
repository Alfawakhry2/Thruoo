# Quick Start Guide - Test Locally in 5 Minutes

## Prerequisites Check
```bash
php -v          # Should be 8.2+
mysql --version # MySQL should be installed
composer --version
```

---

## Step 1: Setup Environment (2 minutes)

### 1.1 Create .env file
```bash
# If .env doesn't exist, copy from example
copy .env.example .env
```

### 1.2 Edit .env - Set these values:
```env
APP_URL=http://thruoo.local
TENANT_DOMAIN=thruoo.local

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thruoo_landlord
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

### 1.3 Generate key
```bash
php artisan key:generate
```

---

## Step 2: Create Database & Run Migrations (1 minute)

### 2.1 Create landlord database
```bash
mysql -u root -p
```
Then in MySQL:
```sql
CREATE DATABASE thruoo_landlord;
EXIT;
```

### 2.2 Run landlord migrations
```bash
php artisan migrate --path=database/migrations/landlord
```

---

## Step 3: Configure Hosts File (1 minute)

### Windows:
1. Open Notepad as **Administrator**
2. Open: `C:\Windows\System32\drivers\etc\hosts`
3. Add these lines:
```
127.0.0.1 thruoo.local
127.0.0.1 acme.thruoo.local
```
4. Save

### Linux/Mac:
```bash
sudo nano /etc/hosts
```
Add:
```
127.0.0.1 thruoo.local
127.0.0.1 acme.thruoo.local
```

---

## Step 4: Start Server (30 seconds)

```bash
php artisan serve --host=thruoo.local --port=8000
```

**Keep this terminal open!**

---

## Step 5: Test It! (1 minute)

### Open a NEW terminal and run:

### 5.1 Register Tenant
```bash
curl -X POST http://thruoo.local:8000/api/tenants/register -H "Content-Type: application/json" -d "{\"company_name\":\"Acme Corp\",\"subdomain\":\"acme\",\"email\":\"admin@acme.com\",\"password\":\"password123\",\"password_confirmation\":\"password123\",\"name\":\"John Doe\",\"modules\":[\"sales\"]}"
```

**Expected:** JSON response with `"success": true`

### 5.2 Login
```bash
curl -X POST http://acme.thruoo.local:8000/api/auth/login -H "Content-Type: application/json" -d "{\"email\":\"admin@acme.com\",\"password\":\"password123\"}"
```

**Copy the `token` from the response!**

### 5.3 Create Lead (replace YOUR_TOKEN)
```bash
curl -X POST http://acme.thruoo.local:8000/api/sales/leads -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -d "{\"name\":\"Jane Smith\",\"email\":\"jane@example.com\",\"status\":\"new\"}"
```

**Expected:** JSON response with created lead

### 5.4 List Leads
```bash
curl -X GET http://acme.thruoo.local:8000/api/sales/leads -H "Authorization: Bearer YOUR_TOKEN"
```

---

## ✅ Success!

If you see JSON responses, everything is working!

**Next:** See `TESTING.md` for detailed testing scenarios.

---

## Common Issues

### "Tenant not found"
- Check hosts file is saved
- Restart server: `php artisan serve --host=thruoo.local --port=8000`

### "Database connection refused"
- Check MySQL is running
- Verify DB credentials in `.env`

### "Access denied"
- MySQL user needs CREATE DATABASE privilege
- Run: `GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost'; FLUSH PRIVILEGES;`

