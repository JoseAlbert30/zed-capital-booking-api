# Handover Requirements System

## Overview
This system tracks and validates that users have uploaded all required handover documents before they can access the booking platform. Documents uploaded by one co-owner are automatically shared with all other co-owners of the same unit(s).

## Required Documents

### For All Users
1. **Payment Proof** (`payment_proof`) - Proof of final payment
2. **AC Connection Documents** (`ac_connection`) - Zenner AC/Chilled Water connection receipt
3. **DEWA Connection Documents** (`dewa_connection`) - DEWA receipt with premise number
4. **Service Charge Acknowledgement** (`service_charge_ack`) - Signed service charge undertaking letter
5. **Developer Handover NOC** (`developer_noc`) - No objection certificate from developer

### For Users with Mortgage
6. **Bank Handover NOC** (`bank_noc`) - No objection certificate from bank (required only if `has_mortgage = true`)

## Database Schema

### Users Table - New Fields
```sql
handover_ready BOOLEAN DEFAULT false  -- Automatically set to true when all requirements met
has_mortgage BOOLEAN DEFAULT false    -- Determines if bank NOC is required
```

### User Attachments Table - New Types
```sql
type ENUM(
  'soa',                    -- Statement of Account
  'contract',               -- Contract documents
  'id',                     -- ID card
  'passport',               -- Passport
  'emirates_id',            -- Emirates ID
  'visa',                   -- Visa
  'receipt',                -- Receipt
  'other',                  -- Other documents
  'payment_proof',          -- ✨ NEW: Payment proof
  'ac_connection',          -- ✨ NEW: AC connection docs
  'dewa_connection',        -- ✨ NEW: DEWA connection docs
  'service_charge_ack',     -- ✨ NEW: Service charge acknowledgement
  'developer_noc',          -- ✨ NEW: Developer NOC
  'bank_noc'                -- ✨ NEW: Bank NOC (if mortgage)
)
```

## API Endpoints

### 1. Upload Attachment
**Endpoint:** `POST /api/users/{user}/upload-attachment`  
**Auth:** Admin only  
**Content-Type:** `multipart/form-data`

**Request:**
```json
{
  "file": <file>,
  "type": "payment_proof|ac_connection|dewa_connection|service_charge_ack|developer_noc|bank_noc"
}
```

**Response:**
```json
{
  "message": "File uploaded successfully",
  "attachment": {
    "id": 123,
    "user_id": 45,
    "filename": "payment_receipt.pdf",
    "type": "payment_proof",
    "created_at": "2026-01-13T07:30:00.000000Z"
  }
}
```

**Behavior:**
- Uploads document for the specified user
- Automatically creates same attachment record for all co-owners
- Adds timeline remark for the user and all co-owners
- Checks if all requirements are met and updates `handover_ready` status
- Accepted file types: PDF, JPG, JPEG, PNG (max 10MB)

### 2. Get Handover Status
**Endpoint:** `GET /api/users/{user}/handover-status`  
**Auth:** Admin or the user themselves

**Response:**
```json
{
  "handover_ready": false,
  "has_mortgage": true,
  "requirements": [
    {
      "type": "payment_proof",
      "label": "Payment Proof",
      "uploaded": true,
      "required": true
    },
    {
      "type": "ac_connection",
      "label": "AC Connection Documents",
      "uploaded": false,
      "required": true
    },
    {
      "type": "dewa_connection",
      "label": "DEWA Connection Documents",
      "uploaded": false,
      "required": true
    },
    {
      "type": "service_charge_ack",
      "label": "Service Charge Acknowledgement",
      "uploaded": false,
      "required": true
    },
    {
      "type": "developer_noc",
      "label": "Developer Handover NOC",
      "uploaded": false,
      "required": true
    },
    {
      "type": "bank_noc",
      "label": "Bank Handover NOC",
      "uploaded": false,
      "required": true
    }
  ]
}
```

### 3. Update Mortgage Status
**Endpoint:** `PUT /api/users/{user}/mortgage-status`  
**Auth:** Admin only

**Request:**
```json
{
  "has_mortgage": true
}
```

**Response:**
```json
{
  "message": "Mortgage status updated successfully",
  "user": {
    "id": 45,
    "full_name": "John Doe",
    "has_mortgage": true,
    "handover_ready": false,
    ...
  }
}
```

**Behavior:**
- Updates the `has_mortgage` field
- Re-evaluates `handover_ready` status (bank NOC becomes required/not required)
- Adds timeline remark

## Co-Owner Document Sharing

### How It Works
When any handover document is uploaded for a user:

1. The attachment is created for the user who uploaded it
2. System finds all co-owners (users who share units with this user)
3. Same attachment record is automatically created for each co-owner
4. Timeline remarks are added for all users indicating the upload
5. Handover ready status is recalculated for the user and all co-owners

### Shared Document Types
The following document types are automatically shared with co-owners:
- `soa` (Statement of Account)
- `payment_proof`
- `ac_connection`
- `dewa_connection`
- `service_charge_ack`
- `developer_noc`
- `bank_noc`

### Example Scenario
**Users:** Alice (primary buyer) and Bob (co-buyer) own Unit 1A-123

**Action:** Admin uploads DEWA connection docs for Alice

**Result:**
- Alice gets attachment record for `dewa_connection`
- Bob automatically gets same attachment record for `dewa_connection`
- Both users get timeline remarks
- Both users' `handover_ready` status is recalculated

## Handover Ready Logic

### Automatic Status Updates
The `handover_ready` field is automatically updated when:
1. A handover document is uploaded
2. Mortgage status is changed

### Requirements Check
```php
$requiredDocs = [
    'payment_proof',
    'ac_connection',
    'dewa_connection',
    'service_charge_ack',
    'developer_noc'
];

if ($user->has_mortgage) {
    $requiredDocs[] = 'bank_noc';
}

// handover_ready = true if ALL required docs are uploaded
```

### Timeline Remarks
When `handover_ready` changes to `true`, a system remark is added:
> "All handover requirements completed - User is ready for booking platform access"

## Integration with Booking Platform

### Access Control
To restrict booking platform access to users who have completed handover requirements:

```php
// In your booking middleware or controller
if (!$user->handover_ready) {
    return response()->json([
        'message' => 'Please complete all handover requirements before booking',
        'handover_status_url' => "/api/users/{$user->id}/handover-status"
    ], 403);
}
```

### Frontend Check
```typescript
// Check handover status before allowing access
const checkHandoverStatus = async (userId: number) => {
  const response = await fetch(
    `${API_URL}/users/${userId}/handover-status`,
    {
      headers: { Authorization: `Bearer ${token}` }
    }
  );
  
  const data = await response.json();
  
  if (!data.handover_ready) {
    // Show requirements checklist
    // Disable booking features
  }
};
```

## Admin Workflow

### Step-by-Step Process

1. **Check User's Mortgage Status**
   - Determine if user has mortgage
   - Update via `PUT /api/users/{user}/mortgage-status`

2. **Upload Required Documents**
   - Upload each document type via `POST /api/users/{user}/upload-attachment`
   - Documents automatically shared with co-owners
   - System tracks completion progress

3. **Verify Handover Readiness**
   - Check `GET /api/users/{user}/handover-status`
   - Ensure all requirements show `uploaded: true`
   - Confirm `handover_ready: true`

4. **Grant Platform Access**
   - Once `handover_ready = true`, user can access booking platform
   - User sees confirmation in timeline

## Testing

### Test Handover Flow
```bash
# 1. Check initial status
GET /api/users/45/handover-status

# 2. Set mortgage status
PUT /api/users/45/mortgage-status
{
  "has_mortgage": true
}

# 3. Upload documents one by one
POST /api/users/45/upload-attachment
- file: payment_receipt.pdf
- type: payment_proof

POST /api/users/45/upload-attachment
- file: ac_receipt.pdf
- type: ac_connection

# ... continue for all required docs

# 4. Verify completion
GET /api/users/45/handover-status
# Should show handover_ready: true
```

### Test Co-Owner Sharing
```bash
# Given: User 45 (Alice) and User 46 (Bob) are co-owners

# 1. Upload doc for Alice
POST /api/users/45/upload-attachment
- file: dewa_receipt.pdf
- type: dewa_connection

# 2. Check Bob's attachments
GET /api/users/46
# Bob should have dewa_connection attachment too

# 3. Check Bob's status
GET /api/users/46/handover-status
# Should reflect the uploaded document
```

## Migration Files

1. **2026_01_13_070213_add_handover_attachment_types_to_user_attachments_table.php**
   - Adds new attachment type enums

2. **2026_01_13_070234_add_handover_ready_to_users_table.php**
   - Adds `handover_ready` boolean field
   - Adds `has_mortgage` boolean field

## Files Modified

### Backend
- `app/Http/Controllers/UserController.php`
  - Updated `uploadAttachment()` to handle new types and co-owner sharing
  - Added `updateHandoverReadyStatus()` helper method
  - Added `getHandoverStatus()` endpoint
  - Added `updateMortgageStatus()` endpoint

- `app/Models/User.php`
  - Added `handover_ready` and `has_mortgage` to fillable

- `routes/api.php`
  - Added `GET /api/users/{user}/handover-status`
  - Added `PUT /api/users/{user}/mortgage-status`

## Notes

- All handover documents are stored in `storage/app/public/attachments/`
- File naming pattern: `{original_name}_{timestamp}.{extension}`
- Maximum file size: 10MB
- Allowed file types: PDF, JPG, JPEG, PNG
- Document sharing happens automatically for co-owners
- Status updates are real-time and automatic
- Timeline remarks are created for all document uploads

---

**System is ready to track handover requirements!** ✅
