<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New POP - {{ $pop->pop_number ?? $popNumber ?? '' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .details {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .details-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #6b7280;
        }
        .value {
            color: #111827;
        }
        .highlight {
            color: #16a34a;
            font-weight: bold;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .note-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .note-box h4 {
            margin-top: 0;
            color: #92400e;
        }
        .note-box ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .note-box li {
            margin-bottom: 5px;
            color: #78350f;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">New POP Received</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">{{ $pop->pop_number }}</p>
    </div>
    
    <div class="content">
        <p>Dear {{ $magicLink->developer_name ?? 'Developer' }},</p>
        
        <p>We have received a new proof of payment for <strong>{{ $pop->project_name }}</strong>. Please review and process this payment at your earliest convenience.</p>
        
        <div class="details">
            <h3 style="margin-top: 0;">Payment Details</h3>
            <div class="details-row">
                <span class="label">POP Number:</span>
                <span class="value">{{ $pop->pop_number }}</span>
            </div>
            <div class="details-row">
                <span class="label">Unit Number:</span>
                <span class="value">{{ $pop->unit_number }}</span>
            </div>
            @if(isset($pop->amount))
            <div class="details-row">
                <span class="label">Amount:</span>
                <span class="value highlight">AED {{ number_format($pop->amount, 2) }}</span>
            </div>
            @endif
            <div class="details-row">
                <span class="label">Date Received:</span>
                <span class="value">{{ $pop->created_at->format('F d, Y') }}</span>
            </div>
        </div>
        
        <p style="color: #6b7280; font-size: 14px;">
            <strong>Next Steps:</strong><br>
            Please access the developer portal to review the proof of payment document and upload the official receipt for the buyer.
        </p>
        
        <p style="text-align: center;">
            <a href="{{ $portalUrl }}" class="button">Access Developer Portal</a>
        </p>
        
        <div class="note-box">
            <h4>Important Information</h4>
            <ul>
                <li>This secure link is valid for 90 days from the date of this email</li>
                <li>You can upload receipts for all pending POPs in this project</li>
                <li>No password required - click the link to access directly</li>
                <li>The link provides secure, one-time access to the developer portal</li>
            </ul>
        </div>
    </div>
    
    <div class="footer">
        <p>This is an automated notification from the Zed Capital Finance Portal.</p>
        <p>© {{ date('Y') }} Zed Capital Real Estate All rights reserved.</p>
    </div>
</body>
</html>