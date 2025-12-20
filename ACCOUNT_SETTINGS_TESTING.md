# Account Settings Testing Guide

## Overview
This guide will help you test the new Account Settings feature that allows users to update their personal information and company details.

## Features Implemented

### 1. Personal Information
- Full Name
- Email (unique)
- Mobile/Phone
- Password (optional update)
- Title (job title/position)
- Birth Year
- How did you know about us (multiple selections)
- Avatar/Profile Picture

### 2. Company Details (Owner/Admin Only)
- Company Name
- City
- Country
- Industry
- Website
- Company Phone
- Company WhatsApp
- Address
- Business Email
- Legal ID
- Tax ID
- Social Media Links:
  - Facebook
  - Instagram
  - LinkedIn
  - Snapchat
  - TikTok
  - YouTube
- Company Logo

---

## Prerequisites

1. **Register a tenant first** (if you haven't):
```bash
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

2. **Login to get authentication token**:
```bash
POST http://testcompany.thruoo.local/api/auth/login
Content-Type: application/json

{
  "email": "john@testcompany.com",
  "password": "password123"
}
```

**Save the token** from the response - you'll need it for all authenticated requests.

---

## API Endpoints Testing

### 1. Get Account Settings
Get current user's personal info and company details.

**Request:**
```bash
GET http://testcompany.thruoo.local/api/account/settings
Authorization: Bearer {your-token-here}
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "personal_info": {
      "name": "John Doe",
      "email": "john@testcompany.com",
      "phone": "+1234567890",
      "title": "ceo",
      "birth_year": 1990,
      "how_know_us": ["google", "friend"],
      "avatar": null
    },
    "company_details": {
      "company_name": "Test Company",
      "city": "New York",
      "country": "USA",
      "industry": "technology",
      "website": null,
      "company_phone": "+1234567890",
      "company_whatsapp": null,
      "address": null,
      "business_email": null,
      "legal_id": null,
      "tax_id": null,
      "facebook": null,
      "instagram": null,
      "linkedin": null,
      "snapchat": null,
      "tiktok": null,
      "youtube": null,
      "logo": null
    }
  }
}
```

---

### 2. Update Personal Information
Any authenticated user can update their own personal info.

**Request:**
```bash
PUT http://testcompany.thruoo.local/api/account/personal-info
Authorization: Bearer {your-token-here}
Content-Type: application/json

{
  "name": "John Updated Doe",
  "phone": "+1234567899",
  "title": "cto",
  "birth_year": 1985,
  "how_know_us": ["linkedin", "blog"]
}
```

**Optional: Update Password**
```json
{
  "name": "John Doe",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Personal information updated successfully",
  "data": {
    "name": "John Updated Doe",
    "email": "john@testcompany.com",
    "phone": "+1234567899",
    "title": "cto",
    "birth_year": 1985,
    "how_know_us": ["linkedin", "blog"],
    "avatar": null
  }
}
```

---

### 3. Update Company Details (Owner/Admin Only)
Only company owner or Super Admin can update company details.

**Request:**
```bash
PUT http://testcompany.thruoo.local/api/account/company-details
Authorization: Bearer {your-token-here}
Content-Type: application/json

{
  "company_name": "Test Company Updated",
  "city": "San Francisco",
  "country": "USA",
  "industry": "technology",
  "website": "https://testcompany.com",
  "company_phone": "+1234567890",
  "company_whatsapp": "+1234567890",
  "address": "123 Tech Street, Silicon Valley",
  "business_email": "business@testcompany.com",
  "legal_id": "LEGAL12345",
  "tax_id": "TAX67890",
  "facebook": "https://facebook.com/testcompany",
  "instagram": "https://instagram.com/testcompany",
  "linkedin": "https://linkedin.com/company/testcompany",
  "youtube": "https://youtube.com/@testcompany"
}
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Company details updated successfully",
  "data": {
    "company_name": "Test Company Updated",
    "city": "San Francisco",
    "country": "USA",
    "industry": "technology",
    "website": "https://testcompany.com",
    "company_phone": "+1234567890",
    "company_whatsapp": "+1234567890",
    "address": "123 Tech Street, Silicon Valley",
    "business_email": "business@testcompany.com",
    "legal_id": "LEGAL12345",
    "tax_id": "TAX67890",
    "facebook": "https://facebook.com/testcompany",
    "instagram": "https://instagram.com/testcompany",
    "linkedin": "https://linkedin.com/company/testcompany",
    "youtube": "https://youtube.com/@testcompany",
    "snapchat": null,
    "tiktok": null,
    "logo": null
  }
}
```

**Error Response (Non-Owner/Non-Admin):**
```json
{
  "success": false,
  "message": "You do not have permission to update company details"
}
```

---

### 4. Upload Avatar
Upload or update user's profile picture.

**Request (using Postman/Form Data):**
```bash
POST http://testcompany.thruoo.local/api/account/avatar
Authorization: Bearer {your-token-here}
Content-Type: multipart/form-data

avatar: [Select an image file]
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Avatar uploaded successfully",
  "data": {
    "avatar": "http://testcompany.thruoo.local/storage/avatars/xxxxx.jpg"
  }
}
```

---

### 5. Upload Company Logo (Owner/Admin Only)
Upload or update company logo.

**Request (using Postman/Form Data):**
```bash
POST http://testcompany.thruoo.local/api/account/logo
Authorization: Bearer {your-token-here}
Content-Type: multipart/form-data

logo: [Select an image file]
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Company logo uploaded successfully",
  "data": {
    "logo": "http://testcompany.thruoo.local/storage/logos/xxxxx.png"
  }
}
```

---

## Testing Scenarios

### Scenario 1: Complete Personal Profile Update
1. Login as a user
2. GET `/api/account/settings` to see current data
3. PUT `/api/account/personal-info` with all fields
4. Upload avatar using POST `/api/account/avatar`
5. GET `/api/account/settings` again to verify updates

### Scenario 2: Update Password
1. Login as a user
2. PUT `/api/account/personal-info` with password fields:
```json
{
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```
3. Logout
4. Try logging in with new password

### Scenario 3: Company Details Update (Owner)
1. Login as company owner
2. GET `/api/account/settings` to see current company details
3. PUT `/api/account/company-details` with updated information
4. Upload company logo using POST `/api/account/logo`
5. Verify updates

### Scenario 4: Permission Test (Non-Owner)
1. Create a second user (invite team member)
2. Login as the second user
3. Try to PUT `/api/account/company-details`
4. Should receive 403 Forbidden error

---

## Validation Rules

### Personal Info
- **name**: required, string, max 255 characters
- **email**: required, valid email, unique, max 255 characters
- **phone**: required, string, max 20 characters
- **password**: optional, min 8 characters, must be confirmed
- **title**: optional, string, max 100 characters
- **birth_year**: optional, between 1940 and (current year - 16)
- **how_know_us**: optional, array of valid options
- **avatar**: optional, must be image, max 2MB

### Company Details
- **company_name**: required, string, max 255 characters
- **city**: required, string, max 100 characters
- **country**: required, string, max 100 characters
- **industry**: required, string, max 100 characters
- **website**: optional, valid URL, max 255 characters
- **company_phone**: optional, string, max 20 characters
- **company_whatsapp**: optional, string, max 20 characters
- **business_email**: optional, valid email, max 255 characters
- **address**: optional, string, max 500 characters
- **legal_id**: optional, string, max 100 characters
- **tax_id**: optional, string, max 100 characters
- **Social media links**: optional, valid URLs, max 255 characters each
- **logo**: optional, must be image, max 2MB

---

## Common Errors

### 1. Validation Errors (422)
```json
{
  "success": false,
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password confirmation does not match."]
  }
}
```

### 2. Authentication Required (401)
```json
{
  "message": "Unauthenticated."
}
```

### 3. Permission Denied (403)
```json
{
  "success": false,
  "message": "You do not have permission to update company details"
}
```

### 4. Invalid Image (422)
```json
{
  "success": false,
  "errors": {
    "avatar": ["The avatar must be an image.", "The avatar must not be greater than 2048 kilobytes."]
  }
}
```

---

## Tips for Testing

1. **Use Postman** for easy API testing
2. **Save your auth token** as an environment variable
3. **Test file uploads** using multipart/form-data
4. **Test validation** by sending invalid data
5. **Test permissions** by logging in as different user roles
6. **Check database** to verify data is actually updated

---

## Database Verification

After updates, check the database:

```sql
-- Check user updates
SELECT * FROM tenant_testcompany.users WHERE email = 'john@testcompany.com';

-- Check tenant/company updates
SELECT * FROM thruoo_landlord.tenants WHERE subdomain = 'testcompany';
```

---

## Next Steps

After successful testing:
1. Build frontend forms for these endpoints
2. Add image preview functionality
3. Implement real-time validation
4. Add success/error notifications
5. Create comprehensive user documentation
