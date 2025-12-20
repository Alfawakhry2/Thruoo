# LEADS SYSTEM IMPLEMENTATION - Complete Guide

## ✅ What I've Created So Far

### 1. Migrations (Ready!)
Created in `database/migrations/tenant/`:
- ✅ `2025_12_20_100001_create_modules_table.php`
- ✅ `2025_12_20_100002_create_lead_sources_table.php`
- ✅ `2025_12_20_100003_create_lead_statuses_table.php`
- ✅ `2025_12_20_100004_create_leads_table.php`

### 2. Models (Ready!)
Created in `app/Models/Modules/Leads/`:
- ✅ `Module.php`
- ✅ `LeadSource.php`
- ✅ `LeadStatus.php`
- ✅ `Lead.php`

---

## 📋 DATABASE STRUCTURE

### Modules Table
```
- id
- name (English name)
- name_ar (Arabic name)
- description
- status (active/inactive)
- subscription_start
- trial_end
- timestamps
```

### Lead Sources Table
```
- id
- name (English name)
- name_ar (Arabic name)
- description
- status (active/inactive)
- created_by (user_id)
- timestamps
```

### Lead Statuses Table
```
- id
- name (English name)
- name_ar (Arabic name)
- description
- order (for sorting)
- color (hex color for UI)
- status (active/inactive)
- created_by (user_id)
- timestamps
```

### Leads Table
```
- id
- name, email, phone, company, position
- city, state, country, address, zip_code, website
- needs, lead_value, priority
- source_id, status_id, assigned_to, created_by, module_id
- notes, custom_fields (JSON)
- last_contacted_at, converted_at, is_converted
- timestamps, soft_deletes
```

---

## 🚀 INSTALLATION STEPS

### Step 1: Run Migrations
```bash
php artisan migrate --path=database/migrations/tenant
```

This will create all 4 tables in your tenant database.

### Step 2: Seed Default Data (Optional)
You can create seeders to add default lead sources and statuses.

---

## 🎯 NEXT: CREATE CONTROLLERS

I'll create the controllers in the next response. Here's what we need:

### Controllers to Create:
1. **ModuleController** - Manage modules
2. **LeadSourceController** - Manage lead sources
3. **LeadStatusController** - Manage lead statuses  
4. **LeadController** - Main leads CRUD

### Routes to Add:
```php
// Modules
GET    /api/modules
POST   /api/modules
PUT    /api/modules/{id}
DELETE /api/modules/{id}

// Lead Sources
GET    /api/lead-sources
POST   /api/lead-sources
PUT    /api/lead-sources/{id}
DELETE /api/lead-sources/{id}

// Lead Statuses
GET    /api/lead-statuses
POST   /api/lead-statuses
PUT    /api/lead-statuses/{id}
DELETE /api/lead-statuses/{id}

// Leads
GET    /api/leads
POST   /api/leads
GET    /api/leads/{id}
PUT    /api/leads/{id}
DELETE /api/leads/{id}
POST   /api/leads/{id}/assign
POST   /api/leads/{id}/convert
GET    /api/leads/stats
```

---

## 📊 FEATURES INCLUDED

### Modules Management:
- Create/update/delete modules
- Activate/deactivate modules
- Track subscription and trial periods

### Lead Sources Management:
- Bilingual support (English & Arabic)
- Active/inactive status
- Track who created it

### Lead Statuses Management:
- Bilingual support (English & Arabic)
- Custom ordering
- Color coding for UI
- Active/inactive status

### Leads Management:
- Complete contact information
- Lead value tracking
- Priority levels (low, medium, high)
- Assignment to users
- Conversion tracking
- Custom fields (JSON)
- Soft deletes
- Search functionality
- Multiple filters

---

## 🔒 PERMISSIONS

| Action | Owner | Admin | User |
|--------|-------|-------|------|
| **Modules** |
| Create/Edit/Delete | ✅ | ✅ | ❌ |
| View | ✅ | ✅ | ✅ |
| **Lead Sources** |
| Create/Edit/Delete | ✅ | ✅ | ❌ |
| View | ✅ | ✅ | ✅ |
| **Lead Statuses** |
| Create/Edit/Delete | ✅ | ✅ | ❌ |
| View | ✅ | ✅ | ✅ |
| **Leads** |
| Create | ✅ | ✅ | ✅ |
| View All | ✅ | ✅ | ❌ |
| View Assigned | ✅ | ✅ | ✅ |
| Edit All | ✅ | ✅ | ❌ |
| Edit Assigned | ✅ | ✅ | ✅ |
| Delete | ✅ | ✅ | ❌ |
| Assign | ✅ | ✅ | ❌ |
| Convert | ✅ | ✅ | ✅ |

---

## 📝 SAMPLE API RESPONSES

### Create Lead Source:
```json
POST /api/lead-sources
{
  "name": "Website",
  "name_ar": "الموقع الإلكتروني",
  "description": "Leads from website contact form",
  "status": "active"
}

Response:
{
  "success": true,
  "message": "Lead source created successfully",
  "data": {
    "id": 1,
    "name": "Website",
    "name_ar": "الموقع الإلكتروني",
    "status": "active",
    "created_by": 1,
    "created_at": "2025-12-20T10:00:00Z"
  }
}
```

### Create Lead Status:
```json
POST /api/lead-statuses
{
  "name": "New",
  "name_ar": "جديد",
  "description": "Newly created lead",
  "color": "#3B82F6",
  "order": 1,
  "status": "active"
}
```

### Create Lead:
```json
POST /api/leads
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "company": "ABC Corp",
  "position": "CEO",
  "city": "New York",
  "country": "USA",
  "lead_value": 50000.00,
  "priority": "high",
  "source_id": 1,
  "status_id": 1,
  "needs": "Looking for CRM solution",
  "module_id": 1
}
```

---

## 🎨 FRONTEND FEATURES TO BUILD

### 1. Modules Page
- List all modules
- Create/edit module modal
- Activate/deactivate toggle
- Subscription management

### 2. Lead Settings Page
Tabs:
- **Sources Tab**: Manage lead sources
- **Statuses Tab**: Manage lead statuses (drag & drop ordering)

### 3. Leads Page
- **List View**: Table with filters
- **Kanban View**: Drag & drop by status
- **Search**: Name, email, phone, company
- **Filters**: Status, source, assigned user, priority, date range
- **Quick Actions**: Assign, convert, delete
- **Bulk Actions**: Bulk assign, bulk delete

### 4. Lead Details Page
- Contact information
- Lead value & priority
- Notes section
- Activity timeline
- Custom fields
- Convert to customer button

---

## ⚙️ DEFAULT DATA TO SEED

### Default Lead Sources:
1. Website - الموقع الإلكتروني
2. Phone Call - مكالمة هاتفية
3. Email - بريد إلكتروني
4. Referral - إحالة
5. Social Media - وسائل التواصل الاجتماعي
6. Walk-in - زيارة مباشرة
7. Trade Show - معرض تجاري
8. Advertisement - إعلان

### Default Lead Statuses:
1. New - جديد (#3B82F6 - Blue)
2. Contacted - تم الاتصال (#8B5CF6 - Purple)
3. Qualified - مؤهل (#10B981 - Green)
4. Proposal Sent - تم إرسال عرض (#F59E0B - Orange)
5. Negotiation - تفاوض (#EC4899 - Pink)
6. Won - فاز (#22C55E - Success Green)
7. Lost - خسر (#EF4444 - Red)

---

## 🔄 LEAD LIFECYCLE

```
New → Contacted → Qualified → Proposal Sent → Negotiation → Won/Lost
```

**Converted Leads:**
- When a lead is marked as "Won", it can be converted to a customer
- `is_converted = true`
- `converted_at` timestamp is set
- Lead remains in system but marked as converted

---

## 📈 STATISTICS TO TRACK

- Total leads
- Leads by status
- Leads by source
- Leads by priority
- Leads by assigned user
- Conversion rate
- Average lead value
- Leads created this month
- Top performing sources
- Top performing users

---

Ready to create the controllers! 🚀

Shall I create all the controllers and routes now?
