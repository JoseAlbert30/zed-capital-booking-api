#!/bin/bash

# Base directory
EMAIL_DIR="/Users/webdeveloper/Downloads/Jose Backup/zedcapital.booking/booking-backend/resources/views/emails"

# Common email template function
create_email() {
    local filename="$1"
    local title="$2"
    local content_body="$3"
    
    cat > "$EMAIL_DIR/${filename}" << 'TEMPLATE_EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TITLE_PLACEHOLDER</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f5f5f5; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .logo-header { background-color: #000000; padding: 30px 20px; text-align: center; }
        .logo-header img { max-width: 200px; height: auto; }
        .content { padding: 40px 30px; }
        .greeting { font-size: 18px; color: #000000; margin-bottom: 20px; font-weight: 600; }
        .message { color: #333333; font-size: 15px; line-height: 1.8; margin-bottom: 30px; }
        .info-box { background-color: #f9f9f9; border: 2px solid #e0e0e0; padding: 25px; margin: 25px 0; }
        .info-box h3 { color: #000000; font-size: 16px; margin-bottom: 20px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #000000; padding-bottom: 10px; }
        .info-row { padding: 12px 0; border-bottom: 1px solid #e0e0e0; display: table; width: 100%; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #666666; display: table-cell; width: 45%; }
        .info-value { color: #000000; font-weight: 500; display: table-cell; text-align: right; }
        .button { display: inline-block; background-color: #000000; color: #ffffff; padding: 15px 35px; text-decoration: none; margin: 25px 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .divider { height: 2px; background-color: #e0e0e0; margin: 30px 0; }
        .footer { background-color: #f9f9f9; padding: 30px; text-align: center; color: #666666; font-size: 13px; border-top: 2px solid #e0e0e0; }
        .alert-box { background-color: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; margin: 25px 0; }
        .warning-box { background-color: #fff3e0; border-left: 4px solid #ff9800; padding: 20px; margin: 25px 0; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="logo-header">
            <img src="{{ asset('storage/letterheads/zed.png') }}" alt="Zed Capital">
        </div>
        <div class="content">
CONTENT_BODY_PLACEHOLDER
            <div class="divider"></div>
            <p class="message">Best regards,<br><strong>Zed Capital Finance Team</strong></p>
        </div>
        <div class="footer">
            <p><strong>Zed Capital Real Estate</strong></p>
            <p style="margin-top: 10px;">This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
TEMPLATE_EOF

    # Replace placeholders   
    sed -i '' "s/TITLE_PLACEHOLDER/$title/g" "$EMAIL_DIR/${filename}"
}

# Generate all email templates
echo "Generating email templates..."

# NOC emails  
create_email "noc-sent-to-buyer.blade.php" "NOC Document"
create_email "noc-request-notification.blade.php" "NOC Request"
create_email "noc-document-uploaded-notification.blade.php" "NOC Uploaded"

# SOA emails
create_email "soa-sent-to-buyer.blade.php" "Statement of Account"
create_email "soa-request-notification.blade.php" "SOA Request"
create_email "soa-uploaded-notification.blade.php" "SOA Uploaded"
create_email "soa-payment-reminder.blade.php" "Payment Reminder"

# Thirdparty emails
create_email "thirdparty-to-buyer.blade.php" "Thirdparty Form"
create_email "thirdparty-to-developer.blade.php" "Thirdparty Submission"
create_email "thirdparty-receipt-to-buyer.blade.php" "Thirdparty Receipt"

# Penalty emails
create_email "penalty-request-notification.blade.php" "Penalty Request"
create_email "penalty-proof-uploaded.blade.php" "Penalty Payment Uploaded"
create_email "penalty-receipt-uploaded.blade.php" "Penalty Receipt Uploaded"
create_email "penalty-document-uploaded-notification.blade.php" "Penalty Document Uploaded"
create_email "penalty-admin-uploaded-document.blade.php" "Penalty Document"
create_email "penalty-to-admin-notification.blade.php" "Penalty Notification"

# POP email
create_email "pop-developer-notification.blade.php" "Payment Notification"

# Receipt email
create_email "receipt-uploaded-notification.blade.php" "Receipt Uploaded"

echo "Done! All email templates generated."
