<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Receipt - {{ $popNumber }}</title>
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
        <h1 style="margin: 0;">Your Payment Receipt</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">{{ $popNumber }}</p>
    </div>
    
    <div class="content">
        <p>Dear {{ $buyerName }},</p>
        
        <p>Your payment receipt is now ready for your records. Please find the details below:</p>
        
        <div class="details">
            <h3 style="margin-top: 0;">Receipt Details</h3>
            <div class="details-row">
                <span class="label">POP Number:</span>
                <span class="value">{{ $popNumber }}</span>
            </div>
            <div class="details-row">
                <span class="label">Unit Number:</span>
                <span class="value">{{ $unitNumber }}</span>
            </div>
            <div class="details-row">
                <span class="label">Project:</span>
                <span class="value">{{ $projectName }}</span>
            </div>
            <div class="details-row">
                <span class="label">Receipt Date:</span>
                <span class="value">{{ date('F j, Y') }}</span>
            </div>
        </div>
        
        <p style="text-align: center;">
            <a href="{{ $receiptUrl }}" class="button">Download Receipt</a>
        </p>
        
        <p>Please keep this receipt for your records. If you have any questions regarding this payment, please contact our finance department.</p>
        
        <div class="note-box">
            <h4>Important</h4>
            <ul>
                <li>This receipt confirms your payment has been processed</li>
                <li>Keep this document for your records</li>
                <li>Contact our finance team if you need any clarification</li>
            </ul>
        </div>
        
        <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
            <strong>Zed Capital - Finance Team</strong><br>
            For any questions or assistance, please contact finance@zedcapital.ae
        </p>
    </div>
    
    <div class="footer">
        <p>This is an automated notification from the Zed Capital Finance Portal.</p>
        <p>© {{ date('Y') }} Zed Capital Real Estate All rights reserved.</p>
    </div>
</body>
</html>
