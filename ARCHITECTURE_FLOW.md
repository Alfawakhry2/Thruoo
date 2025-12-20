# System Architecture & Flow

## 🏗️ Complete System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                        THRUOO CRM SYSTEM                             │
└─────────────────────────────────────────────────────────────────────┘
                                   │
                                   │
                    ┌──────────────┴──────────────┐
                    │                             │
              ┌─────▼─────┐                ┌─────▼─────┐
              │ LANDLORD  │                │  TENANT   │
              │ (Central) │                │ (Customer)│
              └───────────┘                └───────────┘
```

---

## 🔄 Registration Flow (Already Working!)

```
User → Registration Form
       │
       ├─ Step 1: Personal Info
       │  └─ name, email, password, phone, title, birth_year, how_know_us
       │
       ├─ Step 2: Company Info
       │  └─ company_name, subdomain, industry, staff_count, country, city
       │
       ├─ Step 3: Team Members (Optional)
       │  └─ Add team members with roles
       │
       └─ Step 4: Modules & Referral
          └─ Select modules (sales, etc.), referral code

                    │
                    ▼
       POST /api/registration/register
                    │
                    ▼
        ┌───────────────────────┐
        │  Create Tenant        │
        │  - landlord database  │
        │  - tenant database    │
        │  - admin user         │
        └───────────────────────┘
                    │
                    ▼
              ✅ Success!
         Return: tenant info, subdomain, trial info
```

---

## 🔐 Authentication Flow (Already Working!)

```
User → Login Form
       │
       └─ email, password
                    │
                    ▼
       POST http://testco.thruoo.local/api/auth/login
                    │
                    ▼
          Resolve Tenant (from subdomain)
                    │
                    ▼
          Check Credentials
                    │
                    ▼
          Generate Sanctum Token
                    │
                    ▼
              ✅ Return Token
         (Use for all authenticated requests)
```

---

## ⚙️ Account Settings Flow (NEW!)

### 1. View Settings
```
User → Account Settings Page
       │
       └─ GET /api/account/settings
          + Authorization: Bearer token
                    │
                    ▼
          Return:
          - personal_info (from users table)
          - company_details (from tenants table)
```

### 2. Update Personal Info
```
User → Edit Personal Info
       │
       └─ name, email, phone, title, birth_year, etc.
                    │
                    ▼
       PUT /api/account/personal-info
       + Authorization: Bearer token
                    │
                    ▼
          Validate Input
                    │
                    ▼
          Update users table (tenant DB)
                    │
                    ▼
              ✅ Return updated data
```

### 3. Update Company Details (Owner/Admin Only)
```
User → Edit Company Info
       │
       └─ company_name, city, country, social media, etc.
                    │
                    ▼
       PUT /api/account/company-details
       + Authorization: Bearer token
                    │
                    ▼
          Check Permission
          (Owner or Super Admin?)
                    │
         ┌──────────┴──────────┐
         │                     │
        YES                   NO
         │                     │
         ▼                     ▼
    Update tenants table    403 Forbidden
    (landlord DB)           Error
         │
         ▼
    ✅ Return updated data
```

### 4. Upload Avatar/Logo
```
User → Select Image
       │
       └─ Upload file (max 2MB)
                    │
                    ▼
       POST /api/account/avatar (or /logo)
       + Authorization: Bearer token
       + multipart/form-data
                    │
                    ▼
          Validate (image, max 2MB)
                    │
                    ▼
          Store in storage/app/public/
          (avatars/ or logos/)
                    │
                    ▼
          Update database path
                    │
                    ▼
              ✅ Return file URL
```

---

## 🗄️ Database Structure

### Landlord Database (mysql)
```sql
tenants table:
├── id (uuid)
├── name (company name)
├── subdomain (unique)
├── email (unique)
├── phone
├── city
├── country
├── industry
├── website
├── address
├── business_email
├── legal_id
├── tax_id
├── logo
├── settings (JSON) ← Social media links stored here
│   ├── whatsapp
│   ├── facebook
│   ├── instagram
│   ├── linkedin
│   ├── snapchat
│   ├── tiktok
│   └── youtube
├── status
├── plan
├── trial_ends_at
└── enabled_modules (JSON)
```

### Tenant Database (tenant_testco)
```sql
users table:
├── id
├── name
├── email (unique)
├── password
├── phone
├── title
├── birth_year
├── how_know_us (JSON array)
├── avatar
├── is_owner (boolean)
├── status
└── profile_completed
```

---

## 🎯 Permission Matrix

```
┌─────────────────────────┬──────────┬────────┬──────────────┐
│ Action                  │ Any User │ Owner  │ Super Admin  │
├─────────────────────────┼──────────┼────────┼──────────────┤
│ View personal info      │    ✅    │   ✅   │      ✅      │
│ Update personal info    │    ✅    │   ✅   │      ✅      │
│ Change password         │    ✅    │   ✅   │      ✅      │
│ Upload avatar           │    ✅    │   ✅   │      ✅      │
├─────────────────────────┼──────────┼────────┼──────────────┤
│ View company details    │    ✅    │   ✅   │      ✅      │
│ Update company details  │    ❌    │   ✅   │      ✅      │
│ Upload company logo     │    ❌    │   ✅   │      ✅      │
└─────────────────────────┴──────────┴────────┴──────────────┘
```

---

## 🔄 Request/Response Flow

### Example: Update Personal Info

```
┌──────────────┐
│   Browser    │
└──────┬───────┘
       │ PUT /api/account/personal-info
       │ Authorization: Bearer abc123xyz
       │ Content-Type: application/json
       │ {
       │   "name": "John Updated",
       │   "phone": "+9999999999"
       │ }
       ▼
┌─────────────────────────────────────────┐
│         Laravel Middleware              │
├─────────────────────────────────────────┤
│ 1. resolve.tenant (from subdomain)      │
│    → Sets tenant context                │
│                                         │
│ 2. ensure.subscription                  │
│    → Checks if trial/plan active        │
│                                         │
│ 3. auth:sanctum                         │
│    → Validates Bearer token             │
│    → Loads authenticated user           │
└─────────────┬───────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────┐
│    AccountSettingsController            │
│    updatePersonalInfo()                 │
├─────────────────────────────────────────┤
│ 1. Validate request (via FormRequest)   │
│    → UpdatePersonalInfoRequest          │
│                                         │
│ 2. Get authenticated user               │
│    → $user = Auth::user()               │
│                                         │
│ 3. Process data                         │
│    → Hash password if provided          │
│    → Handle avatar if uploaded          │
│                                         │
│ 4. Update database                      │
│    → $user->update($data)               │
│    → Uses tenant DB connection          │
│                                         │
│ 5. Return response                      │
└─────────────┬───────────────────────────┘
              │
              ▼
       ┌──────────────┐
       │  Response    │
       ├──────────────┤
       │ {            │
       │   "success": true,           │
       │   "message": "Updated...",   │
       │   "data": {...}              │
       │ }            │
       └──────────────┘
```

---

## 📦 File Storage Structure

```
storage/
└── app/
    └── public/
        ├── avatars/           ← User profile pictures
        │   ├── abc123.jpg
        │   └── xyz456.png
        │
        └── logos/             ← Company logos
            ├── company1.png
            └── company2.jpg

public/
└── storage/ → ../storage/app/public  (symlink)
    ├── avatars/
    └── logos/
```

**Access URLs:**
- Avatar: `http://testco.thruoo.local/storage/avatars/abc123.jpg`
- Logo: `http://testco.thruoo.local/storage/logos/company1.png`

---

## 🔗 URL Structure

```
Landlord (Registration, etc):
http://thruoo.local/api/registration/register
http://thruoo.local/api/registration/options

Tenant (All other operations):
http://testco.thruoo.local/api/auth/login
http://testco.thruoo.local/api/account/settings
http://testco.thruoo.local/api/account/personal-info
http://testco.thruoo.local/api/account/company-details
http://testco.thruoo.local/api/sales/leads
```

---

## 🧪 Testing Sequence

```
1. Register Tenant
   POST /api/registration/register
   → Creates tenant + database + admin user
   
2. Login
   POST http://testco.thruoo.local/api/auth/login
   → Returns token
   
3. Get Settings
   GET /api/account/settings
   → Returns current data
   
4. Update Personal
   PUT /api/account/personal-info
   → Updates user data
   
5. Upload Avatar
   POST /api/account/avatar
   → Stores image, returns URL
   
6. Update Company
   PUT /api/account/company-details
   → Updates tenant data (if owner)
   
7. Upload Logo
   POST /api/account/logo
   → Stores image, returns URL (if owner)
```

---

## 🎯 Frontend Implementation Guide

### Pages to Create:

1. **Registration Page** (Already Working!)
   - Multi-step form
   - 4 steps as designed

2. **Login Page** (Already Working!)
   - Email + Password
   - Remember me option

3. **Dashboard** (Your existing)
   - User menu with "Account Settings" link

4. **Account Settings Page** (NEW - TO BUILD)
   ```
   ┌─────────────────────────────────────┐
   │  Account Settings                   │
   ├─────────────────────────────────────┤
   │                                     │
   │  ┌─────────┬──────────────────┐    │
   │  │Personal │ Company Details  │    │
   │  │Info     │                  │    │
   │  └─────────┴──────────────────┘    │
   │                                     │
   │  Personal Information               │
   │  ┌───────────────────────────┐     │
   │  │ [Avatar]                  │     │
   │  │ Name: [____________]      │     │
   │  │ Email: [____________]     │     │
   │  │ Phone: [____________]     │     │
   │  │ Title: [____________]     │     │
   │  │ Birth Year: [________]    │     │
   │  │                           │     │
   │  │ [Change Password]         │     │
   │  │ [Save Changes]            │     │
   │  └───────────────────────────┘     │
   │                                     │
   │  Company Details (Owner/Admin)      │
   │  ┌───────────────────────────┐     │
   │  │ [Logo]                    │     │
   │  │ Company: [___________]    │     │
   │  │ City: [___________]       │     │
   │  │ Country: [___________]    │     │
   │  │ Website: [___________]    │     │
   │  │ ...                       │     │
   │  │ [Save Changes]            │     │
   │  └───────────────────────────┘     │
   └─────────────────────────────────────┘
   ```

### State Management:
```javascript
const [personalInfo, setPersonalInfo] = useState({});
const [companyDetails, setCompanyDetails] = useState({});
const [loading, setLoading] = useState(false);
const [error, setError] = useState(null);
```

### API Calls:
```javascript
// Fetch settings on page load
useEffect(() => {
  fetchSettings();
}, []);

// Update personal info
const handleUpdatePersonal = async (data) => {
  // PUT /api/account/personal-info
};

// Upload avatar
const handleAvatarUpload = async (file) => {
  // POST /api/account/avatar
};
```

---

## ✅ Quick Start Checklist

Setup:
- [ ] Copy `AccountSettingsController_FIXED.php` to correct location
- [ ] Run `php artisan migrate`
- [ ] Run `php artisan storage:link`

Testing:
- [ ] Import Postman collection
- [ ] Test registration (create tenant)
- [ ] Test login (get token)
- [ ] Test get settings
- [ ] Test update personal info
- [ ] Test avatar upload
- [ ] Test update company details
- [ ] Test logo upload

Frontend:
- [ ] Design account settings page
- [ ] Create forms
- [ ] Add validation
- [ ] Implement image upload
- [ ] Add success/error notifications

---

This is your complete system architecture! 🎉
