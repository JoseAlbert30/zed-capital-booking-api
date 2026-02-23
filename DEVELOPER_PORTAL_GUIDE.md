# Developer Portal - Complete Implementation Guide

## Overview

The Developer Portal allows real estate developers to receive POP (Proof of Payment) notifications and upload receipts through a secure magic link system, without requiring admin or customer accounts.

## Architecture

### Components

1. **DeveloperMagicLink Model** - Secure token-based access
2. **FinancePOP Model** - Payment proof tracking
3. **DeveloperPortalController** - API endpoints for developer actions
4. **Email System** - Automated notifications with magic links
5. **Frontend Portal** - Developer interface (to be implemented)

## Database Structure

### `developer_magic_links` Table

```sql
- id: Primary key
- project_name: Project the developer has access to
- developer_email: Developer's email address
- developer_name: Developer's name (optional)
- token: Unique 64-character access token
- expires_at: Token expiration (default: 90 days)
- first_used_at: First access timestamp
- last_used_at: Most recent access timestamp
- access_count: Number of times accessed
- ip_address: Last IP address used
- user_agent: Last browser/client used
- is_active: Whether link is still active
- created_at, updated_at: Timestamps
```

### `properties` Table (Updated)

Added fields:
- `developer_email`: Developer's contact email
- `developer_name`: Developer's name

## API Endpoints

### Developer Portal (No Authentication Required)

All endpoints use magic link token for access control.

#### 1. Verify Token
```http
GET /api/developer/verify?token={token}
```

**Response:**
```json
{
  "success": true,
  "developer": {
    "name": "Developer Name",
    "email": "developer@example.com",
    "project": "Project Name"
  },
  "token": "..."
}
```

#### 2. Get POPs
```http
GET /api/developer/pops?token={token}
```
or
```http
GET /api/developer/pops
Headers: X-Developer-Token: {token}
```

**Response:**
```json
{
  "success": true,
  "pops": [
    {
      "id": 1,
      "popNumber": "POP-0001",
      "unitNumber": "Unit 101",
      "amount": 50000.00,
      "attachmentUrl": "...",
      "attachmentName": "pop.pdf",
      "receiptUrl": null,
      "receiptName": null,
      "receiptUploaded": false,
      "date": "2026-02-23"
    }
  ]
}
```

#### 3. Upload Receipt
```http
POST /api/developer/pops/{popId}/receipt
Headers: X-Developer-Token: {token}
Content-Type: multipart/form-data

Body:
- receipt: File (PDF, JPG, JPEG, PNG, max 10MB)
```

**Response:**
```json
{
  "success": true,
  "message": "Receipt uploaded successfully",
  "receipt": {
    "url": "...",
    "name": "receipt.pdf",
    "uploadedAt": "2026-02-23 10:30:00"
  }
}
```

#### 4. Download POP
```http
GET /api/developer/pops/{popId}/download?token={token}
```

Returns the POP file for download.

### Admin API (Authenticated)

#### Create POP and Send to Developer
```http
POST /api/finance/pops
Headers: Authorization: Bearer {admin_token}
Content-Type: multipart/form-data

Body:
- project_name: string (required)
- unit_number: string (required)
- amount: number (required)
- attachment: File (required)
- send_to_developer: 'true' (optional)
```

When `send_to_developer` is 'true':
1. POP is created
2. Magic link is generated (or reused if valid one exists)
3. Email is sent to developer with POP details and magic link

## Email Template

### Subject
```
New Payment - Proof of Payment Received - POP-0001
```

### Body
```
Dear Developer Name,

We have received a new proof of payment for Project Name.

Payment Details:
- POP Number: POP-0001
- Unit Number: Unit 101
- Amount: AED 50,000.00
- Date Received: February 23, 2026

Please access the developer portal to review the proof of payment and upload the official receipt.

[Access Developer Portal Button]

Important Notes:
- This link is valid for 90 days from the date of this email
- You can upload receipts for all pending POPs in this project
- The link provides secure access without requiring a password
```

## Security Features

### Magic Link Security
- **Unique Tokens**: 64-character random string
- **Expiration**: 90 days (configurable)
- **Single Project Access**: Limited to specific project
- **Activity Tracking**: Logs IP, user agent, access count
- **Deactivation**: Can be manually deactivated
- **Auto-Replacement**: New link deactivates previous ones

### Permission Restrictions
Developers can ONLY:
- View POPs for their assigned project
- Download POP attachments
- Upload receipts for POPs
- View their own uploaded receipts

Developers CANNOT:
- Access admin functions
- View other projects
- Modify POP details
- Delete POPs
- Access user data

## Configuration

### Environment Variables

```env
# Frontend URL for magic link generation
FRONTEND_URL=http://localhost:3000

# Email configuration (already set up)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Adding Developer to Project

Update the `properties` table:

```sql
UPDATE properties 
SET developer_email = 'developer@company.com',
    developer_name = 'Company Name'
WHERE project_name = 'Your Project';
```

Or via Laravel:

```php
Property::where('project_name', 'Your Project')->update([
    'developer_email' => 'developer@company.com',
    'developer_name' => 'Company Name',
]);
```

## Frontend Implementation (Next.js)

### Route Structure
```
/developer/portal - Main portal page (checks token)
/developer/portal?token={token} - Direct access with token
```

### Required Pages

1. **Portal Landing** (`/developer/portal/page.tsx`)
   - Verify token
   - Show developer info
   - List all POPs for the project
   - Upload interface for receipts

2. **Components**
   - `DeveloperAuth.tsx` - Token verification wrapper
   - `POPList.tsx` - Display POPs with upload buttons
   - `ReceiptUpload.tsx` - Upload interface
   - `POPDetails.tsx` - View POP details and download

### Example Implementation

```typescript
// app/developer/portal/page.tsx
'use client';

import { useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';

export default function DeveloperPortal() {
  const searchParams = useSearchParams();
  const token = searchParams.get('token');
  const [developer, setDeveloper] = useState(null);
  const [pops, setPOPs] = useState([]);
  
  useEffect(() => {
    if (!token) return;
    
    // Verify token
    fetch(`${API_URL}/developer/verify?token=${token}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setDeveloper(data.developer);
          localStorage.setItem('developer_token', token);
          fetchPOPs(token);
        }
      });
  }, [token]);
  
  const fetchPOPs = async (token) => {
    const res = await fetch(`${API_URL}/developer/pops`, {
      headers: { 'X-Developer-Token': token }
    });
    const data = await res.json();
    if (data.success) setPOPs(data.pops);
  };
  
  const uploadReceipt = async (popId, file) => {
    const formData = new FormData();
    formData.append('receipt', file);
    
    const res = await fetch(`${API_URL}/developer/pops/${popId}/receipt`, {
      method: 'POST',
      headers: { 'X-Developer-Token': token },
      body: formData
    });
    
    if (res.ok) {
      fetchPOPs(token);
      toast.success('Receipt uploaded successfully');
    }
  };
  
  // ... render UI
}
```

## Testing

### 1. Setup Test Project
```sql
INSERT INTO properties (project_name, location, developer_email, developer_name)
VALUES ('Test Project', 'Dubai', 'test@developer.com', 'Test Developer');
```

### 2. Create POP via Admin
```bash
# Use frontend "Add POP & Send" button
# Or via API:
curl -X POST http://localhost:8000/api/finance/pops \
  -H "Authorization: Bearer {admin_token}" \
  -F "project_name=Test Project" \
  -F "unit_number=101" \
  -F "amount=50000" \
  -F "attachment=@pop.pdf" \
  -F "send_to_developer=true"
```

### 3. Check Email
Developer should receive email with magic link

### 4. Access Portal
Click link or navigate to:
```
http://localhost:3000/developer/portal?token={token_from_email}
```

### 5. Upload Receipt
Use the upload interface to submit a receipt file

### 6. Verify in Admin
Check that receipt appears in admin finance panel

## Troubleshooting

### Email Not Sent
- Check `email_logs` table for errors
- Verify MAIL_* environment variables
- Check that project has developer_email set
- Review logs: `tail -f storage/logs/laravel.log`

### Invalid Token
- Check token hasn't expired
- Verify is_active = true
- Check expires_at > current time

### Upload Failed
- Verify file size < 10MB
- Check allowed MIME types (pdf, jpg, jpeg, png)
- Ensure token is valid for that project
- Check storage permissions

### Can't Access POPs
- Verify token belongs to correct project
- Check POP's project_name matches magic link's project_name
- Ensure token is being sent in header or query param

## Best Practices

1. **Token Management**
   - Store token in localStorage on first access
   - Include token in all API requests
   - Clear token on expiration

2. **Error Handling**
   - Check token validity on portal load
   - Show friendly message for expired links
   - Provide contact info for new link requests

3. **File Uploads**
   - Show upload progress
   - Validate file type client-side
   - Display preview before upload
   - Confirm successful upload

4. **Security**
   - Never expose token in URLs after initial access
   - Use HTTPS in production
   - Implement rate limiting
   - Log all developer actions

## Future Enhancements

- [ ] Multi-project access for developers
- [ ] Receipt approval workflow
- [ ] Notification when receipt is accepted
- [ ] Developer dashboard with statistics
- [ ] Bulk receipt upload
- [ ] Receipt rejection with feedback
- [ ] Integration with accounting systems
- [ ] Mobile app for developers
