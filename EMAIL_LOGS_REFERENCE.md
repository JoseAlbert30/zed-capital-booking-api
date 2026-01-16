# Email Logging System - Quick Reference

## Overview
All SOA emails sent through the booking system are automatically logged to the database for tracking and compliance purposes.

## Database Table: `email_logs`

### Schema
```sql
CREATE TABLE email_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    recipient_email VARCHAR(255),
    recipient_name VARCHAR(255),
    subject VARCHAR(255),
    message TEXT,
    email_type VARCHAR(255) DEFAULT 'soa',
    status VARCHAR(255) DEFAULT 'sent',
    error_message TEXT NULL,
    metadata JSON NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Fields Explained

| Field | Description | Example |
|-------|-------------|---------|
| `user_id` | Foreign key to users table | 42 |
| `recipient_email` | Email address of recipient | john@example.com |
| `recipient_name` | Full name of recipient | John Doe |
| `subject` | Email subject line | Statement of Account - Zed Capital |
| `message` | Personalized email body | Dear John Doe, Your unit: A-101... |
| `email_type` | Type of email (soa, notification, etc.) | soa |
| `status` | sent, failed, queued | sent |
| `error_message` | Error details if failed | SMTP connection timeout |
| `metadata` | JSON with units, projects, attachments | {"units": [...], "attachments": [...]} |
| `sent_at` | When email was successfully sent | 2026-01-12 14:30:00 |

## Viewing Email Logs

### Via Database Query
```sql
-- Get all sent emails
SELECT * FROM email_logs ORDER BY created_at DESC;

-- Get emails for specific user
SELECT * FROM email_logs WHERE user_id = 42;

-- Get failed emails
SELECT * FROM email_logs WHERE status = 'failed';

-- Get emails sent today
SELECT * FROM email_logs WHERE DATE(sent_at) = CURDATE();

-- Count emails by status
SELECT status, COUNT(*) as count 
FROM email_logs 
GROUP BY status;
```

### Via API Endpoints

```bash
# Get all email logs (paginated, 50 per page)
GET /api/email/logs
Authorization: Bearer {token}

# Get email logs for specific user
GET /api/email/logs/{userId}
Authorization: Bearer {token}
```

### Example Response
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 42,
      "recipient_email": "john@example.com",
      "recipient_name": "John Doe",
      "subject": "Statement of Account - Zed Capital",
      "email_type": "soa",
      "status": "sent",
      "metadata": {
        "units": [
          {
            "unit": "A-101",
            "project": "Zed Tower 1"
          }
        ],
        "attachments": {
          "soa_filename": "SOA_A-101.pdf"
        }
      },
      "sent_at": "2026-01-12T14:30:00.000000Z",
      "created_at": "2026-01-12T14:30:00.000000Z"
    }
  ]
}
```

## Use Cases

### 1. Audit Trail
Track when and what was sent to each client:
```sql
SELECT 
    recipient_name,
    subject,
    sent_at,
    status
FROM email_logs
WHERE user_id = 42
ORDER BY sent_at DESC;
```

### 2. Compliance Reporting
Generate monthly report of all SOA emails sent:
```sql
SELECT 
    DATE(sent_at) as date,
    COUNT(*) as emails_sent,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
FROM email_logs
WHERE MONTH(sent_at) = 1 AND YEAR(sent_at) = 2026
GROUP BY DATE(sent_at);
```

### 3. Retry Failed Emails
Find all failed emails for retry:
```sql
SELECT 
    id,
    user_id,
    recipient_email,
    error_message
FROM email_logs
WHERE status = 'failed'
ORDER BY created_at DESC;
```

### 4. User Communication History
View all communications with a specific user:
```sql
SELECT 
    sent_at,
    subject,
    email_type,
    status
FROM email_logs
WHERE recipient_email = 'john@example.com'
ORDER BY sent_at DESC;
```

## Metadata JSON Examples

### SOA Email Metadata
```json
{
  "units": [
    {
      "unit": "A-101",
      "project": "Zed Tower 1"
    },
    {
      "unit": "B-205",
      "project": "Zed Tower 2"
    }
  ],
  "attachments": {
    "soa_filename": "SOA_A-101.pdf"
  }
}
```

### Failed Email Metadata
```json
{
  "error_trace": "Exception trace...",
  "smtp_error": "Connection timeout",
  "retry_count": 3
}
```

## Best Practices

1. **Regular Cleanup**: Archive logs older than 1 year
   ```sql
   DELETE FROM email_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
   ```

2. **Monitor Failed Emails**: Set up alerts for failed emails
   ```sql
   SELECT COUNT(*) FROM email_logs 
   WHERE status = 'failed' 
   AND DATE(created_at) = CURDATE();
   ```

3. **Export for Reports**: Export monthly data for compliance
   ```sql
   SELECT * INTO OUTFILE '/tmp/email_logs_jan_2026.csv'
   FIELDS TERMINATED BY ','
   FROM email_logs
   WHERE MONTH(sent_at) = 1 AND YEAR(sent_at) = 2026;
   ```

## Integration with User Details

You can view email history in the user detail page (future enhancement):
- Add email logs tab in `/admin/users/{id}` page
- Show sent emails, failed emails, last email date
- Option to resend failed emails

## Performance Considerations

- **Index on user_id**: Already created via foreign key
- **Index on sent_at**: For date-range queries
  ```sql
  CREATE INDEX idx_sent_at ON email_logs(sent_at);
  ```
- **Index on status**: For filtering by status
  ```sql
  CREATE INDEX idx_status ON email_logs(status);
  ```

## Security

- Email logs contain sensitive information
- Only accessible to admin users via API
- Protected by authentication middleware
- Consider encrypting email message content for compliance

---

**Note**: This is the FIRST STEP in the booking system workflow. All SOA emails are logged here before users can proceed to book units.
