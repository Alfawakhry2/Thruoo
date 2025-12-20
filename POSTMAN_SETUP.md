# Postman Setup for Multi-Tenant Testing

## Problem
Postman might not resolve subdomains from your hosts file correctly. Here are solutions:

---

## Solution 1: Add Subdomain to Hosts File (Recommended)

### Windows:
1. Open Notepad as **Administrator**
2. Open: `C:\Windows\System32\drivers\etc\hosts`
3. Add this line:
```
127.0.0.1 acme.thruoo.local
```
4. Save the file

### Verify:
```bash
ping acme.thruoo.local
```
Should resolve to `127.0.0.1`

---

## Solution 2: Use Postman with Host Header

If hosts file doesn't work in Postman, you can use the `Host` header:

### In Postman:
1. Create a new request
2. URL: `http://127.0.0.1:8000/api/auth/login`
3. Go to **Headers** tab
4. Add header:
   - Key: `Host`
   - Value: `acme.thruoo.local`
5. Send request

---

## Solution 3: Use Postman Environment Variables

1. In Postman, click **Environments** → **Create Environment**
2. Add variables:
   - `base_url`: `http://127.0.0.1:8000`
   - `host_header`: `acme.thruoo.local`
3. In your requests:
   - URL: `{{base_url}}/api/auth/login`
   - Header: `Host: {{host_header}}`

---

## Solution 4: Use curl (Always Works)

If Postman still doesn't work, use curl:

```bash
curl -X POST http://acme.thruoo.local:8000/api/auth/login ^
  -H "Content-Type: application/json" ^
  -H "Host: acme.thruoo.local" ^
  -d "{\"email\":\"admin@acme.com\",\"password\":\"password123\"}"
```

---

## Quick Test Commands

### Register Tenant (Main Domain):
```bash
curl -X POST http://thruoo.local:8000/api/tenants/register -H "Content-Type: application/json" -d "{\"company_name\":\"Acme Corp\",\"subdomain\":\"acme\",\"email\":\"admin@acme.com\",\"password\":\"password123\",\"password_confirmation\":\"password123\",\"name\":\"John Doe\",\"modules\":[\"sales\"]}"
```

### Login (Subdomain):
```bash
curl -X POST http://acme.thruoo.local:8000/api/auth/login -H "Content-Type: application/json" -d "{\"email\":\"admin@acme.com\",\"password\":\"password123\"}"
```

---

## Troubleshooting

### Postman shows "Could not get response"
- Check if server is running: `php artisan serve --host=thruoo.local --port=8000`
- Try using `127.0.0.1:8000` with `Host` header instead
- Disable Postman's proxy settings

### "Tenant not found" error
- Verify tenant exists: Run `php check-tenants.php`
- Check subdomain matches exactly (case-sensitive)
- Ensure tenant status is 'active'

### DNS resolution issues
- Restart Postman after editing hosts file
- Flush DNS cache: `ipconfig /flushdns` (Windows)
- Try using `127.0.0.1` with Host header method

