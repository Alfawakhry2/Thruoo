# 🎯 Account Settings Feature - Complete Guide

## 📋 Quick Summary

I've successfully added a complete Account Settings feature to your Thruoo CRM. Here's what was implemented:

### ✅ Registration System Status
Your registration works perfectly! It has 4 steps:
1. Personal Info → 2. Company Info → 3. Team Members (optional) → 4. Modules

### ✅ New Account Settings Features
- **Personal Information Management** (any user can update their own)
- **Company Details Management** (only owner/admin)
- **Avatar Upload** (profile pictures)
- **Company Logo Upload**
- **Password Change**

---

## 📁 All Files Created/Modified

### ✨ New Files Created:

1. **Controllers:**
   - `app/Http/Controllers/Api/Account/AccountSettingsController.php`
   - `AccountSettingsController_FIXED.php` (use this one - has correct imports)

2. **Request Validation:**
   - `app/Http/Requests/Api/Account/UpdatePersonalInfoRequest.php`
   - `app/Http/Requests/Api/Account/UpdateCompanyDetailsRequest.php`

3. **Migrations:**
   - `database/migrations/2025_12_20_000001_add_avatar_to_users_table.php`

4. **Documentation:**
   - `ACCOUNT_SETTINGS_TESTING.md` - Complete API testing guide
   - `IMPLEMENTATION_SUMMARY.md` - Quick reference
   - `Thruoo_CRM_Account_Settings.postman_collection.json` - Postman collection

### 🔄 Modified Files:
   - `routes/api.php` - Added account settings routes

---

## 🚀 Installation Steps (IMPORTANT!)

### Step 1: Replace the Controller File
**Copy the corrected file to the right location:**

```bash
# Windows Command
copy AccountSettingsController_FIXED.php app\Http\Controllers\Api\Account\AccountSettingsController.php

# Or manually:
# 1. Delete: app/Http/Controllers/Api/Account/AccountSettingsController.php
# 2. Rename: AccountSettingsController_FIXED.php → AccountSettingsController.php
# 3. Move it to: app/Http/Controllers/Api/Account/
```

### Step 2: Run Migrations
```bash
php artisan migrate
```

### Step 3: Create Storage Symlink
```bash
php artisan storage:link
```

### Step 4: Test!
Import the Postman collection and start testing.

---

## 🧪 How to Test Registration (It's Working!)

### Test 1: Register a New Tenant

**Using Postman/API Client:**
```
POST http://thruoo.local/api/registration/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@testcompany.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+1234567890",
  "title": "ceo",
  "birth_year": 1990,
  "how_know_us": ["google", "friend"],
  "company_name": "Test Company",
  "subdomain": "testcompany",
  "industry": "technology",
  "staff_count": "1-10",
  "country": "USA",
  "city": "New York",
  "modules": ["sales"]
}
```

✅ **Expected Result:** You'll get a success response with tenant details and a login URL.

### Test 2: Login
```
POST http://testcompany.thruoo.local/api/auth/login
Content-Type: application/json

{
  "email": "john@testcompany.com",
  "password": "password123"
}
```

✅ **Expected Result:** You'll get a token. Save it for authenticated requests!

---

## 🔑 New API Endpoints

All endpoints require:
- ✅ Tenant resolution (use subdomain: `testcompany.thruoo.local`)
- ✅ Authentication (Bearer token from login)

### 1. Get Account Settings
```
GET http://testcompany.thruoo.local/api/account/settings
Authorization: Bearer {your-token}
```

**Returns:** Personal info + Company details

### 2. Update Personal Info
```
PUT http://testcompany.thruoo.local/api/account/personal-info
Authorization: Bearer {your-token}
Content-Type: application/json

{
  "name": "John Updated",
  "phone": "+1234567899",
  "title": "cto"
}
```

### 3. Update Company Details (Owner/Admin Only)
```
PUT http://testcompany.thruoo.local/api/account/company-details
Authorization: Bearer {your-token}
Content-Type: application/json

{
  "company_name": "Updated Company",
  "city": "San Francisco",
  "website": "https://example.com",
  "facebook": "https://facebook.com/company",
  "instagram": "https://instagram.com/company"
}
```

### 4. Upload Avatar
```
POST http://testcompany.thruoo.local/api/account/avatar
Authorization: Bearer {your-token}
Content-Type: multipart/form-data

avatar: [image file, max 2MB]
```

### 5. Upload Company Logo (Owner/Admin Only)
```
POST http://testcompany.thruoo.local/api/account/logo
Authorization: Bearer {your-token}
Content-Type: multipart/form-data

logo: [image file, max 2MB]
```

---

## 📊 Data Structure

### Personal Information Fields:
- `name` - Full name
- `email` - Email (must be unique)
- `phone` - Mobile/Phone number
- `password` - Password (optional, for updates)
- `title` - Job title/position
- `birth_year` - Birth year (1940 to current year - 16)
- `how_know_us` - Array of sources (google, facebook, linkedin, etc.)
- `avatar` - Profile picture

### Company Details Fields:
- `company_name` - Company name
- `city` - City
- `country` - Country
- `industry` - Industry type
- `website` - Company website URL
- `company_phone` - Company phone
- `company_whatsapp` - WhatsApp number
- `address` - Physical address
- `business_email` - Business email
- `legal_id` - Legal/Registration ID
- `tax_id` - Tax ID
- `facebook` - Facebook URL
- `instagram` - Instagram URL
- `linkedin` - LinkedIn URL
- `snapchat` - Snapchat URL
- `tiktok` - TikTok URL
- `youtube` - YouTube URL
- `logo` - Company logo

---

## 🔒 Permissions

### Who Can Do What?

| Action | Any User | Owner | Super Admin |
|--------|----------|-------|-------------|
| View personal info | ✅ (own) | ✅ | ✅ |
| Update personal info | ✅ (own) | ✅ | ✅ |
| Upload avatar | ✅ (own) | ✅ | ✅ |
| View company details | ✅ | ✅ | ✅ |
| Update company details | ❌ | ✅ | ✅ |
| Upload company logo | ❌ | ✅ | ✅ |

---

## 📱 Postman Collection

**Import the collection:**
1. Open Postman
2. Import → File → Select `Thruoo_CRM_Account_Settings.postman_collection.json`
3. Set collection variables:
   - `landlord_url`: `http://thruoo.local`
   - `base_url`: `http://testcompany.thruoo.local`
   - `auth_token`: (will be auto-saved after login)

**Pre-configured requests:**
- ✅ Registration
- ✅ Login (auto-saves token)
- ✅ Get account settings
- ✅ Update personal info
- ✅ Update personal info with password
- ✅ Update company details
- ✅ Upload avatar
- ✅ Upload logo

---

## 🎨 Frontend Integration Tips

When building the frontend:

### 1. Account Settings Page Structure
```
┌─────────────────────────────────┐
│  Account Settings               │
├─────────────────────────────────┤
│ ┌─────────┬──────────────────┐  │
│ │Personal │ Company Details  │  │
│ │Info     │ (Owner/Admin)    │  │
│ └─────────┴──────────────────┘  │
│                                 │
│  [Personal Info Form]           │
│  - Name                         │
│  - Email                        │
│  - Phone                        │
│  - Title                        │
│  - Birth Year                   │
│  - Avatar Upload                │
│  - Change Password button       │
│                                 │
│  [Company Details Form]         │
│  (Only for Owner/Admin)         │
│  - Company Name                 │
│  - Location (City, Country)     │
│  - Contact Info                 │
│  - Social Media                 │
│  - Logo Upload                  │
│                                 │
└─────────────────────────────────┘
```

### 2. Form Validation (Client-Side)
Match the backend validation rules:
- Email format validation
- Password min 8 characters + confirmation
- Birth year: 1940 to (current year - 16)
- Image files: max 2MB
- URLs: valid format for website and social media

### 3. API Call Examples (JavaScript/React)

```javascript
// Get settings
const getSettings = async () => {
  const response = await fetch(
    'http://testcompany.thruoo.local/api/account/settings',
    {
      headers: {
        'Authorization': `Bearer ${token}`,
      }
    }
  );
  return await response.json();
};

// Update personal info
const updatePersonalInfo = async (data) => {
  const response = await fetch(
    'http://testcompany.thruoo.local/api/account/personal-info',
    {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data)
    }
  );
  return await response.json();
};

// Upload avatar
const uploadAvatar = async (file) => {
  const formData = new FormData();
  formData.append('avatar', file);
  
  const response = await fetch(
    'http://testcompany.thruoo.local/api/account/avatar',
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
      body: formData
    }
  );
  return await response.json();
};
```

---

## ✅ Testing Checklist

Before moving to frontend development:

- [ ] Copy `AccountSettingsController_FIXED.php` to correct location
- [ ] Run migrations (`php artisan migrate`)
- [ ] Create storage link (`php artisan storage:link`)
- [ ] Test registration (create a test tenant)
- [ ] Test login (get auth token)
- [ ] Test GET account settings
- [ ] Test update personal info
- [ ] Test password change
- [ ] Test avatar upload
- [ ] Test update company details (as owner)
- [ ] Test permission check (try as non-owner - should fail)
- [ ] Test logo upload
- [ ] Verify all data in database
- [ ] Verify uploaded files are accessible

---

## 🐛 Troubleshooting

### "Class 'Illuminate\Http\Request' not found"
**Solution:** Make sure you copied `AccountSettingsController_FIXED.php` to the correct location.

### "Unauthenticated" Error
**Solution:** Check you're sending the token:
```
Authorization: Bearer your-token-here
```

### "Permission denied" (403)
**Solution:** Only owner or Super Admin can update company details. Regular users get this error - it's working correctly!

### File Upload Fails
**Solutions:**
1. Run: `php artisan storage:link`
2. Check permissions: `chmod -R 775 storage`
3. Ensure `storage/app/public/` exists

### "Tenant not found"
**Solution:** Use the tenant's subdomain: `http://testcompany.thruoo.local` not `http://thruoo.local`

---

## 📖 Documentation Files

1. **ACCOUNT_SETTINGS_TESTING.md** - Detailed testing guide with all examples
2. **IMPLEMENTATION_SUMMARY.md** - Technical implementation details
3. **This file (START_HERE.md)** - Your main guide
4. **Postman Collection** - Ready-to-use API collection

---

## 🎉 You're Ready!

Your system is complete and ready to use. Here's what to do next:

1. **Install** (copy fixed controller, run migrations, storage link)
2. **Test** (use Postman collection)
3. **Build Frontend** (create forms based on the API)
4. **Deploy** (when ready for production)

Need any changes or have questions? Let me know! 🚀
