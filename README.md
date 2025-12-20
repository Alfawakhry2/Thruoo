# Thruoo CRM - Multi-Tenant SaaS Application

A multi-tenant CRM system built with Laravel 12, Spatie Multitenancy, and Spatie Permission.

## Features

- **Multi-Tenancy**: Database-per-tenant isolation using Spatie Multitenancy
- **Subdomain-based Tenant Resolution**: Each tenant gets a unique subdomain (e.g., `demo.thruoo.local`)
- **Trial System**: 7-day free trial with 3-day grace period
- **Module System**: Modular architecture starting with Sales module
- **Role-Based Permissions**: Using Spatie Permission package
- **API-First**: All endpoints are RESTful APIs

## Installation

### 1. Install Dependencies

```bash
composer install
```

### 2. Environment Configuration

Copy `.env.example` to `.env` and configure:

```env
APP_NAME=Thruoo
APP_URL=http://thruoo.local
TENANT_DOMAIN=thruoo.local

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thruoo_landlord
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Run Landlord Migrations

```bash
php artisan migrate --path=database/migrations/landlord
```

This creates the `tenants` table in the landlord database.

### 5. Configure Local Testing (Hosts File)

#### Windows (`C:\Windows\System32\drivers\etc\hosts`):
```
127.0.0.1 thruoo.local
127.0.0.1 demo.thruoo.local
127.0.0.1 test.thruoo.local
```

#### Linux/Mac (`/etc/hosts`):
```
127.0.0.1 thruoo.local
127.0.0.1 demo.thruoo.local
127.0.0.1 test.thruoo.local
```

## Usage

### Register a New Tenant

**Endpoint:** `POST /api/tenants/register`

**Request:**
```json
{
  "company_name": "Acme Corp",
  "subdomain": "acme",
  "email": "admin@acme.com",
  "password": "password123",
  "password_confirmation": "password123",
  "name": "John Doe",
  "phone": "+1234567890",
  "modules": ["sales"]
}
```

**Response:**
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

This will:
1. Create tenant record in landlord database
2. Create tenant database (`tenant_acme`)
3. Run migrations on tenant database
4. Create admin user
5. Seed roles and permissions

### Login to Tenant

**Endpoint:** `POST http://acme.thruoo.local/api/auth/login`

**Request:**
```json
{
  "email": "admin@acme.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "admin@acme.com",
      "roles": ["Super Admin"],
      "permissions": ["view_leads", "create_leads", ...]
    },
    "token": "sanctum-token-here"
  }
}
```

### Sales Module Endpoints

All Sales endpoints require:
- Tenant resolution (subdomain)
- Active subscription/trial
- Authentication (Sanctum token)
- Sales module enabled

#### Leads

- `GET /api/sales/leads` - List leads
- `POST /api/sales/leads` - Create lead
- `GET /api/sales/leads/{id}` - Get lead
- `PUT /api/sales/leads/{id}` - Update lead
- `DELETE /api/sales/leads/{id}` - Delete lead

**Create Lead Example:**
```bash
curl -X POST http://acme.thruoo.local/api/sales/leads \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "phone": "+1234567890",
    "company": "Example Corp",
    "status": "new",
    "value": 5000.00
  }'
```

#### Deals

- `GET /api/sales/deals` - List deals
- `POST /api/sales/deals` - Create deal
- `GET /api/sales/deals/{id}` - Get deal
- `PUT /api/sales/deals/{id}` - Update deal
- `DELETE /api/sales/deals/{id}` - Delete deal

## Database Structure

### Landlord Database (`thruoo_landlord`)

**tenants table:**
- `id` (uuid)
- `name`
- `subdomain` (unique)
- `domain` (nullable, for custom domains)
- `database` (unique, e.g., `tenant_acme`)
- `email` (unique)
- `phone`
- `status` (active/suspended/cancelled)
- `trial_ends_at`
- `subscription_ends_at`
- `plan` (free/basic/pro/enterprise)
- `enabled_modules` (JSON)
- `settings` (JSON)
- `timestamps`
- `deleted_at` (soft deletes)

### Tenant Database (`tenant_{subdomain}`)

Each tenant has its own database with:
- `users` table
- `leads` table
- `deals` table
- Spatie Permission tables (`roles`, `permissions`, etc.)

## Roles & Permissions

### Default Roles

- **Super Admin**: All permissions
- **Sales Manager**: Manage leads/deals, view reports, manage users
- **Sales Representative**: Create/edit own leads, view deals
- **User**: Basic access

### Sales Module Permissions

- `view_leads`, `create_leads`, `edit_leads`, `delete_leads`
- `view_deals`, `create_deals`, `edit_deals`, `delete_deals`
- `view_reports`, `manage_users`, `manage_settings`

## Middleware

- **resolve.tenant**: Resolves tenant from subdomain and switches database connection
- **ensure.subscription**: Validates tenant has active trial/subscription
- **ensure.module:{module}**: Validates specific module is enabled for tenant

## Testing

### Manual Testing Flow

1. Register tenant via `POST /api/tenants/register`
2. Access tenant subdomain: `http://{subdomain}.thruoo.local`
3. Login: `POST /api/auth/login`
4. Use authenticated endpoints with token

### Example Test Scenario

```bash
# 1. Register tenant
curl -X POST http://thruoo.local/api/tenants/register \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "Test Corp",
    "subdomain": "test",
    "email": "admin@test.com",
    "password": "password123",
    "password_confirmation": "password123",
    "name": "Test Admin",
    "modules": ["sales"]
  }'

# 2. Login
curl -X POST http://test.thruoo.local/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@test.com",
    "password": "password123"
  }'

# 3. Create lead (use token from login response)
curl -X POST http://test.thruoo.local/api/sales/leads \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "status": "new"
  }'
```

## Troubleshooting

### Tenant Not Found (404)

- Ensure subdomain is registered in `tenants` table
- Check hosts file configuration
- Verify `TENANT_DOMAIN` in `.env` matches your setup

### Database Connection Errors

- Ensure MySQL user has CREATE DATABASE privileges
- Check database credentials in `.env`
- Verify tenant database was created successfully

### Permission Denied (403)

- Check tenant status is `active`
- Verify trial/subscription is not expired
- Ensure module is enabled in `enabled_modules` JSON field

## Production Considerations

### Database Management

- **TODO**: Replace raw SQL database creation with cloud DB API calls (AWS RDS, DigitalOcean, etc.)
- Implement database backup strategy per tenant
- Consider connection pooling for high traffic

### Domain Configuration

- Set up wildcard DNS: `*.thruoo.com` → your server IP
- Configure SSL certificates for subdomains (Let's Encrypt with wildcard)
- Update `TENANT_DOMAIN` to production domain

### Security

- Encrypt sensitive tenant data
- Implement rate limiting per tenant
- Add tenant isolation checks in all queries
- Regular security audits

## License

MIT
