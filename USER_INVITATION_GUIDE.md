# USER INVITATION SYSTEM - Complete Guide

## ✅ What's Been Implemented

I've created a complete user invitation system that allows:
1. **Inviting users** (by owner/admin)
2. **Verifying invitation tokens**
3. **Completing registration** (invited users)
4. **Managing invitations** (list, resend, cancel)

---

## 🔄 User Invitation Flow

### Step 1: Owner/Admin Invites a User
```
POST http://testcompany.thruoo.local/api/invitations
Authorization: Bearer {owner-token}
Content-Type: application/json

{
  "email": "newuser@example.com",
  "name": "John New User",
  "role": "Sales"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User invited successfully",
  "data": {
    "user": {
      "id": 2,
      "email": "newuser@example.com",
      "name": "John New User",
      "status": "pending",
      "invited_at": "2025-12-20T10:00:00Z"
    },
    "invitation_token": "abc123xyz..." 
  }
}
```

### Step 2: New User Verifies Token
```
POST http://testcompany.thruoo.local/api/invitations/verify
Content-Type: application/json

{
  "token": "abc123xyz..."
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "email": "newuser@example.com",
    "name": "John New User",
    "invited_at": "2025-12-20T10:00:00Z",
    "expires_at": "2025-12-27T10:00:00Z"
  }
}
```

### Step 3: New User Completes Registration
```
POST http://testcompany.thruoo.local/api/invitations/complete
Content-Type: application/json

{
  "token": "abc123xyz...",
  "name": "John New User",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+1234567890",
  "title": "sales",
  "birth_year": 1995
}
```

**Response:**
```json
{
  "success": true,
  "message": "Registration completed successfully",
  "data": {
    "user": {
      "id": 2,
      "name": "John New User",
      "email": "newuser@example.com",
      "phone": "+1234567890",
      "title": "sales",
      "status": "active"
    },
    "token": "sanctum-token-here"
  }
}
```

Now the user can login normally!

---

## 📋 Management Endpoints (Owner/Admin Only)

### 1. List All Invitations
```
GET http://testcompany.thruoo.local/api/invitations
Authorization: Bearer {owner-token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "users": [
      {
        "id": 2,
        "name": "John New User",
        "email": "newuser@example.com",
        "status": "active",
        "invited_by": "Owner Name",
        "invited_at": "2025-12-20T10:00:00Z",
        "is_expired": false
      },
      {
        "id": 3,
        "name": "Pending User",
        "email": "pending@example.com",
        "status": "pending",
        "invited_by": "Owner Name",
        "invited_at": "2025-12-15T10:00:00Z",
        "is_expired": true
      }
    ],
    "stats": {
      "total": 2,
      "active": 1,
      "pending": 1
    }
  }
}
```

### 2. Resend Invitation
```
POST http://testcompany.thruoo.local/api/invitations/{userId}/resend
Authorization: Bearer {owner-token}
```

### 3. Cancel Invitation (Delete pending user)
```
DELETE http://testcompany.thruoo.local/api/invitations/{userId}
Authorization: Bearer {owner-token}
```

---

## 🔒 Permissions

| Action | Owner | Super Admin | Regular User |
|--------|-------|-------------|--------------|
| Invite users | ✅ | ✅ | ❌ |
| List invitations | ✅ | ✅ | ❌ |
| Resend invitation | ✅ | ✅ | ❌ |
| Cancel invitation | ✅ | ✅ | ❌ |
| Verify token (public) | ✅ | ✅ | ✅ |
| Complete registration (public) | ✅ | ✅ | ✅ |

---

## ⏰ Token Expiration

- Invitation tokens expire after **7 days**
- Expired invitations can be resent
- Expired invitations are marked in the list

---

## 📝 Validation Rules

### Invite User:
- `email`: required, valid email, unique
- `name`: optional, string, max 255
- `role`: optional, one of: Admin, Assistant, Sales, Finance

### Complete Registration:
- `token`: required, valid invitation token
- `name`: required, string, max 255
- `password`: required, min 8 characters, confirmed
- `phone`: required, string, max 20
- `title`: optional, string, max 100
- `birth_year`: optional, between 1940 and (current year - 16)

---

## 🧪 Testing Sequence

### Test 1: Invite a User
1. Login as owner
2. POST `/api/invitations` with user email
3. Get invitation token from response
4. Save it for next steps

### Test 2: Complete Registration
1. POST `/api/invitations/verify` with token (verify it works)
2. POST `/api/invitations/complete` with all details
3. Get auth token in response
4. User is now active!

### Test 3: Login as New User
1. POST `/api/auth/login` with new user credentials
2. Should work normally

### Test 4: Manage Invitations
1. Login as owner
2. GET `/api/invitations` to see all users
3. Try resending an invitation
4. Try cancelling a pending invitation

---

## 🎨 Frontend Implementation

### Invite User Page (Owner/Admin)
```
┌─────────────────────────────────┐
│  Team Members                   │
├─────────────────────────────────┤
│                                 │
│  [+ Invite New User]            │
│                                 │
│  Active Members (2)             │
│  ┌───────────────────────────┐ │
│  │ John Doe (Owner)          │ │
│  │ john@company.com          │ │
│  └───────────────────────────┘ │
│                                 │
│  │ Jane Smith (Active)       │ │
│  │ jane@company.com          │ │
│  │ [Suspend] [Edit]          │ │
│  └───────────────────────────┘ │
│                                 │
│  Pending Invitations (1)        │
│  ┌───────────────────────────┐ │
│  │ Bob Wilson               │ │
│  │ bob@company.com          │ │
│  │ Invited 3 days ago       │ │
│  │ [Resend] [Cancel]        │ │
│  └───────────────────────────┘ │
└─────────────────────────────────┘
```

### Invitation Acceptance Page (Public)
```
┌─────────────────────────────────┐
│  Complete Your Registration     │
├─────────────────────────────────┤
│                                 │
│  You've been invited to join    │
│  Test Company                   │
│                                 │
│  Email: newuser@example.com     │
│                                 │
│  Full Name: [____________]      │
│  Password:  [____________]      │
│  Confirm:   [____________]      │
│  Phone:     [____________]      │
│  Title:     [▼ Dropdown]        │
│  Birth Year:[________]          │
│                                 │
│  [Complete Registration]        │
└─────────────────────────────────┘
```

---

## 🚨 Important Notes

1. **Email Sending**: Currently returns token in response for testing. In production:
   - Remove `invitation_token` from response
   - Send token via email
   - Use email templates

2. **Security**:
   - Tokens are 64 characters random string
   - Tokens expire after 7 days
   - One-time use (deleted after completion)

3. **User Status**:
   - `pending`: Invited but not registered
   - `active`: Completed registration
   - `suspended`: Account suspended

---

## 📤 Email Template (TODO)

When sending invitation email:

```
Subject: You're invited to join {Company Name} on Thruoo CRM

Hi {Name},

{Inviter Name} has invited you to join {Company Name} on Thruoo CRM.

Click the link below to complete your registration:
{Frontend URL}/invitations/accept?token={token}

This invitation expires in 7 days.

If you didn't expect this invitation, you can safely ignore this email.
```

---

## ✅ Testing Checklist

Setup:
- [ ] User invitation controller created
- [ ] Routes updated
- [ ] Leads/Deals removed from routes

Testing:
- [ ] Invite a user (as owner)
- [ ] Verify invitation token
- [ ] Complete registration
- [ ] Login as new user
- [ ] List all invitations
- [ ] Resend invitation
- [ ] Cancel invitation
- [ ] Test permission checks (try as regular user)

---

## 🎁 What's Next?

After testing invitation system:
1. Build frontend invite modal
2. Build registration completion page
3. Add email notifications
4. Start building Leads module (fresh start!)

---

Ready to test! 🚀
