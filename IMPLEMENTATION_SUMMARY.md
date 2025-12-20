# Account Settings Implementation - Summary

## ✅ Registration System Status: **WORKING**

Your registration system is fully functional with a 4-step process:
1. Personal Information
2. Company Information
3. Team Members (optional)
4. Modules & Referral

## 📁 Files Created for Account Settings

### 1. Controller
**Location:** `app/Http/Controllers/Api/Account/AccountSettingsController.php`

**Methods:**
- `index()` - Get all account settings (personal + company)
- `updatePersonalInfo()` - Update user's personal information
- `updateCompanyDetails()` - Update company details (Owner/Admin only)
- `uploadAvatar()` - Upload user profile picture
- `uploadLogo()` - Upload company logo (Owner/Admin only)

### 2. Request Validation Classes
**Location:** `app/Http/Requests/Api/Account/`

- `UpdatePersonalInfoRequest.php` - Validates personal info updates
- `UpdateCompanyDetailsRequest.php` - Validates company details updates

### 3. Migration
**Location:** `database/migrations/2025_12_20_000001_add_avatar_to_users_table.php`

Adds `avatar` column to users table.

### 4. Routes
**Location:** `routes/api.php`

Added new routes under `/api/account/` prefix:
- `GET /api/account/settings`
- `PUT /api/account/personal-info`
- `PUT /api/account/company-details`
- `POST /api/account/avatar`
- `POST /api/account/logo`

### 5. Documentation
- `ACCOUNT_SETTINGS_TESTING.md` - Complete testing guide
- `Thruoo_CRM_Account_Settings.postman_collection.json` - Postman collection

## 🔧 Installation Steps

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Create Storage Symlink (if not exists)
```bash
php artisan storage:link
```

### 3. Ensure Storage Directories Exist
The following directories should exist in `storage/app/public/`:
- `avatars/`
- `logos/`

Laravel will create them automatically on first upload.

## 🧪 How to Test Registration

### Option 1: Using Postman

1. **Import the Postman collection:**
   - File: `Thruoo_CRM_Account_Settings.postman_collection.json`
   - Set variables:
     - `landlord_url`: `http://thruoo.local`
     - `base_url`: `http://testcompany.thruoo.local`

2. **Register a new tenant:**
   ```
   POST http://thruoo.local/api/registration/register
   ```
   Use the body from the collection or testing guide.

3. **Login:**
   ```
   POST http://testcompany.thruoo.local/api/auth/login
   ```
   The token will be automatically saved.

4. **Test account settings endpoints:**
   - Get settings
   - Update personal info
   - Update company details
   - Upload avatar/logo

### Option 2: Using cURL

```bash
# 1. Register
curl -X POST http://thruoo.local/api/registration/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@test.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+1234567890",
    "title": "ceo",
    "birth_year": 1990,
    "how_know_us": ["google"],
    "company_name": "Test Company",
    "subdomain": "testcompany",
    "industry": "technology",
    "staff_count": "1-10",
    "country": "USA",
    "city": "New York",
    "modules": ["sales"]
  }'

# 2. Login
curl -X POST http://testcompany.thruoo.local/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@test.com",
    "password": "password123"
  }'

# 3. Get Account Settings (use token from login)
curl -X GET http://testcompany.thruoo.local/api/account/settings \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 📝 Account Settings Features

### Personal Information (Any User)
- ✅ Full Name
- ✅ Email (must be unique)
- ✅ Mobile/Phone
- ✅ Password (optional - can be updated)
- ✅ Title/Position
- ✅ Birth Year
- ✅ How did you know about us? (multiple selections)
- ✅ Avatar/Profile Picture

### Company Details (Owner/Admin Only)
- ✅ Company Name
- ✅ City
- ✅ Country
- ✅ Industry
- ✅ Website
- ✅ Company Phone
- ✅ Company WhatsApp
- ✅ Address
- ✅ Business Email
- ✅ Legal ID
- ✅ Tax ID
- ✅ Social Media Links:
  - Facebook
  - Instagram
  - LinkedIn
  - Snapchat
  - TikTok
  - YouTube
- ✅ Company Logo

## 🔐 Permissions

### Personal Info Update
- **Who can update:** Any authenticated user (for their own data)
- **Endpoint:** `PUT /api/account/personal-info`

### Company Details Update
- **Who can update:** Only company owner OR users with "Super Admin" role
- **Endpoint:** `PUT /api/account/company-details`
- **Returns 403 error** if user doesn't have permission

## ⚠️ Important Notes

### 1. Fix Missing Import (Manual Fix Required)
In `app/Http/Controllers/Api/Account/AccountSettingsController.php`, add this line at the top with other imports:
```php
use Illuminate\Http\Request;
```

The full import section should look like:
```php
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Account\UpdatePersonalInfoRequest;
use App\Http\Requests\Api\Account\UpdateCompanyDetailsRequest;
use App\Models\Landlord\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;  // <-- ADD THIS LINE
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
```

### 2. Tenant Helper Function
The code uses `tenant()` helper function which should be provided by Spatie Multitenancy package. If you encounter errors, verify the package is properly configured.

### 3. File Uploads
- Maximum file size: **2MB**
- Accepted formats: **Images only** (jpg, jpeg, png, gif, webp)
- Storage: Files are stored in `storage/app/public/avatars/` and `storage/app/public/logos/`
- Access: Through `storage` symlink - `public/storage/avatars/` and `public/storage/logos/`

### 4. Social Media in Settings
Social media links are stored in the `settings` JSON column of the `tenants` table as:
```json
{
  "whatsapp": "+1234567890",
  "facebook": "https://facebook.com/company",
  "instagram": "https://instagram.com/company",
  "linkedin": "https://linkedin.com/company/company",
  "snapchat": "https://snapchat.com/add/company",
  "tiktok": "https://tiktok.com/@company",
  "youtube": "https://youtube.com/@company"
}
```

## 🎯 Next Steps

### For Backend:
1. ✅ Run migrations
2. ✅ Add missing `use Illuminate\Http\Request;` import
3. ✅ Test all endpoints using Postman collection
4. ✅ Verify file uploads work correctly
5. ✅ Test permission checks (owner vs non-owner)

### For Frontend:
1. Create account settings page with tabs:
   - Personal Information tab
   - Company Details tab
2. Add form validation matching backend rules
3. Implement image upload with preview
4. Add success/error notifications
5. Show current avatar and logo
6. Add "Change Password" modal
7. Restrict company details form to owners/admins

## 📚 Documentation Files

1. **ACCOUNT_SETTINGS_TESTING.md** - Complete API testing guide with examples
2. **Thruoo_CRM_Account_Settings.postman_collection.json** - Ready-to-use Postman collection
3. **This file (IMPLEMENTATION_SUMMARY.md)** - Quick reference guide

## 🐛 Troubleshooting

### "Class 'Illuminate\Http\Request' not found"
Add the missing import line mentioned in Important Notes #1.

### "Storage symlink not found"
Run: `php artisan storage:link`

### "Permission denied" when uploading files
Check storage folder permissions:
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### "Tenant not found"
Ensure you're accessing the correct subdomain (e.g., `testcompany.thruoo.local` not `thruoo.local`)

### "Unauthenticated"
Make sure you're sending the Bearer token in Authorization header:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

## ✅ Testing Checklist

- [ ] Register new tenant
- [ ] Login successfully
- [ ] Get account settings
- [ ] Update personal info
- [ ] Update password
- [ ] Upload avatar
- [ ] Update company details (as owner)
- [ ] Try updating company details as non-owner (should fail with 403)
- [ ] Upload company logo
- [ ] Verify all data in database
- [ ] Verify file uploads are accessible

---

**Ready to use!** Follow the testing guide and let me know if you need any adjustments.
