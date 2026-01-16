# Email SMTP Setup Guide for Zed Capital Booking System

This guide will help you configure email sending functionality for the booking system, enabling automated SOA emails, booking confirmations, and other notifications.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [SMTP Configuration](#smtp-configuration)
3. [Popular Email Service Providers](#popular-email-service-providers)
4. [Testing Email Configuration](#testing-email-configuration)
5. [Troubleshooting](#troubleshooting)
6. [Performance & Bulk Sending](#performance--bulk-sending)

---

## Prerequisites

- Laravel application (already set up)
- Email service provider account (Gmail, SendGrid, Mailgun, etc.)
- Access to `.env` file in your Laravel backend

---

## SMTP Configuration

### Step 1: Configure `.env` File

Open your `.env` file located in the `booking-backend` directory and update the following settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Zed Capital Booking System"
```

### Step 2: Clear Configuration Cache

After updating `.env`, run:

```bash
php artisan config:clear
php artisan cache:clear
```

---

## Popular Email Service Providers

### 1. **Gmail (For Development/Small Scale)**

#### Setup Steps:
1. Enable 2-Factor Authentication on your Google Account
2. Generate an App Password:
   - Go to https://myaccount.google.com/apppasswords
   - Select "Mail" and your device
   - Copy the generated 16-character password

#### Configuration:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Zed Capital"
```

**Limitations:**
- 500 emails per day (free Gmail)
- 2000 emails per day (Google Workspace)
- Not recommended for production bulk sending

---

### 2. **SendGrid (Recommended for Production)**

SendGrid offers 100 free emails per day and is highly reliable for bulk sending.

#### Setup Steps:
1. Sign up at https://sendgrid.com
2. Create an API Key:
   - Go to Settings → API Keys → Create API Key
   - Give it "Full Access" or "Mail Send" permission
3. Verify your sender email/domain

#### Configuration:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=YOUR_SENDGRID_API_KEY
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=verified@yourdomain.com
MAIL_FROM_NAME="Zed Capital"
```

**Benefits:**
- 100 emails/day free (12,000/month paid plans start at $19.95)
- Excellent deliverability
- Analytics and tracking
- Can handle 200+ emails per batch

---

### 3. **Mailgun (Good Alternative)**

#### Setup Steps:
1. Sign up at https://mailgun.com
2. Add and verify your domain
3. Get your SMTP credentials from the dashboard

#### Configuration:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@your-domain.mailgun.org
MAIL_PASSWORD=your-mailgun-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Zed Capital"
```

**Benefits:**
- 5,000 free emails for first 3 months
- Then 1,000 free emails per month
- Good for production use

---

### 4. **Amazon SES (Cost-Effective for Large Scale)**

#### Configuration:
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS=verified@yourdomain.com
MAIL_FROM_NAME="Zed Capital"
```

**Benefits:**
- $0.10 per 1,000 emails
- Highly scalable
- 62,000 free emails per month if sent from EC2

---

## Testing Email Configuration

### Method 1: Using Tinker

```bash
php artisan tinker
```

Then run:
```php
Mail::raw('This is a test email from Zed Capital Booking System', function ($message) {
    $message->to('test@example.com')
            ->subject('Test Email');
});
```

### Method 2: Create a Test Route

Add to `routes/web.php`:
```php
Route::get('/test-email', function () {
    Mail::raw('Test email from Zed Capital', function ($message) {
        $message->to('your-email@example.com')
                ->subject('Test Email');
    });
    return 'Email sent!';
});
```

Visit: `http://your-domain.com/test-email`

---

## Activating Email Sending in the Code

### Step 1: Run Migrations

```bash
php artisan migrate
```

This creates the `email_logs` table to track all sent emails.

### Step 2: Enable Email Sending

Open `app/Http/Controllers/EmailController.php` and **uncomment** this section (around line 78):

```php
// UNCOMMENT THIS:
Mail::send([], [], function ($mail) use ($user, $subject, $personalizedMessage) {
    $mail->to($user->email, $user->full_name)
        ->subject($subject)
        ->html(nl2br($personalizedMessage));
    
    // Attach SOA PDF here
    // $soaPath = storage_path('app/soa/' . $user->units->first()->unit . '.pdf');
    // if (file_exists($soaPath)) {
    //     $mail->attach($soaPath, [
    //         'as' => 'SOA_' . $user->units->first()->unit . '.pdf',
    //         'mime' => 'application/pdf',
    //     ]);
    // }
});
```

---

## Performance & Bulk Sending

### Current System Capabilities

✅ **Can handle 144 recipients easily**
✅ **Max 200 recipients per API call** (configured limit)
✅ **Email logs stored in database** for tracking
✅ **Transaction-safe** (all logs saved even if some emails fail)

### For Very Large Batches (200+ users)

If you need to send to more than 200 users, consider implementing **Laravel Queues**:

#### 1. Set up queue driver in `.env`:
```env
QUEUE_CONNECTION=database
```

#### 2. Create queue table:
```bash
php artisan queue:table
php artisan migrate
```

#### 3. Create a Mail Job:
```bash
php artisan make:job SendSOAEmail
```

#### 4. Process the queue:
```bash
php artisan queue:work
```

This allows you to send thousands of emails without timeout issues.

---

## Troubleshooting

### Issue: "Connection could not be established"
**Solution:** 
- Check MAIL_HOST and MAIL_PORT are correct
- Ensure firewall allows outbound connections on port 587/465
- Verify SMTP credentials

### Issue: "Emails not being received"
**Solution:**
- Check spam/junk folders
- Verify sender email is verified with your provider
- Check email logs in `storage/logs/laravel.log`
- Check `email_logs` table in database

### Issue: "Too many login attempts" (Gmail)
**Solution:**
- Use App Password instead of regular password
- Enable "Less secure app access" (not recommended)
- Switch to SendGrid or Mailgun

### Issue: Timeout with bulk emails
**Solution:**
- Implement queue system (see above)
- Reduce batch size in frontend
- Increase PHP `max_execution_time` in `php.ini`

---

## Security Best Practices

1. **Never commit `.env` file** to version control
2. **Use App Passwords** for Gmail (never your main password)
3. **Verify sender domains** to improve deliverability
4. **Enable SPF and DKIM records** on your domain
5. **Monitor email logs** for suspicious activity
6. **Rotate SMTP credentials** periodically

---

## Recommended Setup for Zed Capital

For a booking system sending SOAs to 144 recipients:

### Development:
- **Gmail with App Password** (free, easy setup)

### Production:
- **SendGrid** (reliable, 100/day free, good analytics)
- Or **Mailgun** (5,000 free for 3 months)

### Configuration Steps:
1. Choose provider (SendGrid recommended)
2. Update `.env` with SMTP credentials
3. Run `php artisan migrate` to create email_logs table
4. Uncomment email sending code in EmailController.php
5. Test with a few emails first
6. Monitor `email_logs` table and Laravel logs

---

## Support

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check email_logs table: `SELECT * FROM email_logs ORDER BY created_at DESC;`
3. Test SMTP connection using tinker
4. Verify your SMTP credentials are correct

For SendGrid-specific issues: https://docs.sendgrid.com/
For Mailgun support: https://documentation.mailgun.com/

---

**Last Updated:** January 12, 2026
**System Version:** Zed Capital Booking System v1.0
