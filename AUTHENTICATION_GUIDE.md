# Authentication Guide - How to Use Tokens

## Problem: "Not Authenticated" Error

When you get "not authenticated" error, it's usually because:
1. Token is not being sent in the request
2. Token format is incorrect
3. Token has expired
4. Wrong Authorization header format

---

## Step-by-Step: Getting Your Token

### Step 1: Login to Get Token

**URL:** `http://said.thruoo.local:8000/api/auth/login`

**Method:** POST

**Headers:**
```
Content-Type: application/json
```

**Body (raw JSON):**
```json
{
  "email": "your-email@said.com",
  "password": "your-password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { ... },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
  }
}
```

**⚠️ IMPORTANT:** Copy the `token` value from the response!

---

## Step 2: Use Token in Authenticated Requests

### In Postman:

1. **Create a new request**
2. **URL:** `http://said.thruoo.local:8000/api/auth/me`
3. **Method:** GET
4. **Go to "Headers" tab**
5. **Add this header:**
   - **Key:** `Authorization`
   - **Value:** `Bearer YOUR_TOKEN_HERE`
   
   Replace `YOUR_TOKEN_HERE` with the actual token from Step 1.

6. **Send the request**

### Example Header:
```
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**⚠️ IMPORTANT:** 
- The word "Bearer" must be included (with capital B)
- There must be a space after "Bearer"
- The token must be the exact value from login response

---

## Using curl (Command Line)

### Login:
```bash
curl -X POST http://said.thruoo.local:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"your-email@said.com","password":"your-password"}'
```

### Get User Info (with token):
```bash
curl -X GET http://said.thruoo.local:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Common Mistakes

### ❌ Wrong: Missing "Bearer"
```
Authorization: 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### ✅ Correct: With "Bearer"
```
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### ❌ Wrong: No space after Bearer
```
Authorization: Bearer1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### ✅ Correct: Space after Bearer
```
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### ❌ Wrong: Using wrong header name
```
Token: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### ✅ Correct: Using Authorization header
```
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## Postman Setup (Detailed)

### Option 1: Manual Header (Recommended)

1. Open Postman
2. Create new request
3. Set method to **GET**
4. Enter URL: `http://said.thruoo.local:8000/api/auth/me`
5. Click **Headers** tab
6. Click **Add Header**
7. Enter:
   - **Key:** `Authorization`
   - **Value:** `Bearer 1|your-actual-token-here`
8. Click **Send**

### Option 2: Postman Authorization Tab

1. Open Postman
2. Create new request
3. Set method to **GET**
4. Enter URL: `http://said.thruoo.local:8000/api/auth/me`
5. Click **Authorization** tab
6. Select **Type:** `Bearer Token`
7. Paste your token in the **Token** field
8. Click **Send**

---

## Testing the Flow

### 1. First, Login:
```bash
POST http://said.thruoo.local:8000/api/auth/login
Content-Type: application/json

{
  "email": "admin@said.com",
  "password": "password123"
}
```

**Copy the token from response!**

### 2. Then, Get User Info:
```bash
GET http://said.thruoo.local:8000/api/auth/me
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## Troubleshooting

### Error: "Unauthenticated" or "Not authenticated"

**Check:**
1. ✅ Token is included in Authorization header
2. ✅ Format is: `Bearer YOUR_TOKEN`
3. ✅ No extra spaces or characters
4. ✅ Token hasn't expired
5. ✅ You're using the correct subdomain (`said.thruoo.local`)

### Error: "Tenant not found"

**Check:**
1. ✅ Subdomain is correct (`said`)
2. ✅ Tenant exists in database
3. ✅ Tenant status is 'active'
4. ✅ Hosts file is configured correctly

### Error: "The provided credentials are incorrect"

**Check:**
1. ✅ Email is correct
2. ✅ Password is correct
3. ✅ User exists in tenant database
4. ✅ You're logging in to the correct tenant subdomain

---

## Quick Test Script

Save this and run it (replace credentials):

```bash
# Login
TOKEN=$(curl -s -X POST http://said.thruoo.local:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@said.com","password":"password123"}' \
  | grep -o '"token":"[^"]*' | cut -d'"' -f4)

echo "Token: $TOKEN"

# Get user info
curl -X GET http://said.thruoo.local:8000/api/auth/me \
  -H "Authorization: Bearer $TOKEN"
```

---

## Summary

1. **Login** → Get token
2. **Copy token** from response
3. **Add Authorization header** with format: `Bearer YOUR_TOKEN`
4. **Make authenticated requests**

The key is the **Authorization header** with **Bearer** prefix! 🔑

