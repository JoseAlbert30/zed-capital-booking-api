<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Uploaded - {{ $popNumber }}</title>
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
        .highlight {
            color: #16a34a;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">Receipt Uploaded</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">{{ $popNumber }}</p>
    </div>
    
    <div class="content">
        <p>Hello Admin Team,</p>
        
        <p>A receipt has been uploaded by <strong>{{ $developerName }}</strong> for the following proof of payment:</p>
        
        <div class="details">
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
                <span class="label">Amount:</span>
                <span class="value highlight">AED {{ number_format($amount, 2) }}</span>
            </div>
            <div class="details-row">
                <span class="label">Uploaded By:</span>
                <span class="value">{{ $developerName }}</span>
            </div>
            <div class="details-row">
                <span class="label">Upload Date:</span>
                <span class="value">{{ now()->format('F d, Y H:i') }}</span>
            </div>
        </div>
        
        <p style="text-align: center;">
            <a href="{{ $receiptUrl }}" class="button">View Receipt</a>
        </p>
        
        <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
            <strong>Next Steps:</strong><br>
            Please review the uploaded receipt and verify the payment details. You can access the receipt through the finance portal or by clicking the button above.
        </p>
    </div>
    
    <div class="footer">
        <p>This is an automated notification from the Zed Capital Finance Portal.</p>
        <p>© {{ date('Y') }} Vantage Ventures Real Estate Development L.L.C. All rights reserved.</p>
    </div>
</body>
</html>
