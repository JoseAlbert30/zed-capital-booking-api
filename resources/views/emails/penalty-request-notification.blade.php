<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penalty Notice</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .penalty-details {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .detail-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #fee2e2;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #991b1b;
            min-width: 140px;
        }
        .detail-value {
            color: #1f2937;
            flex: 1;
        }
        .amount-highlight {
            font-size: 20px;
            font-weight: 700;
            color: #991b1b;
        }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
            transition: all 0.3s ease;
        }
        .action-button:hover {
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.4);
        }
        .description-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .description-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        .description-text {
            color: #4b5563;
            line-height: 1.6;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .warning-note {
            background-color: #fef3c7;
            border: 1px solid #fcd34d;
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
            color: #92400e;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>⚠️ Penalty Notice</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                Dear {{ $developerName }},
            </div>
            
            <p>A penalty has been issued for <strong>Unit {{ $unitNumber }}</strong> in <strong>{{ $projectName }}</strong>.</p>
            
            <div class="penalty-details">
                <div class="detail-row">
                    <span class="detail-label">Penalty Number:</span>
                    <span class="detail-value"><strong>{{ $penaltyNumber }}</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Penalty Name:</span>
                    <span class="detail-value">{{ $penaltyName }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Unit Number:</span>
                    <span class="detail-value">{{ $unitNumber }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Project:</span>
                    <span class="detail-value">{{ $projectName }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value amount-highlight">AED {{ number_format($amount, 2) }}</span>
                </div>
            </div>

            @if($description)
            <div class="description-box">
                <div class="description-label">Description:</div>
                <div class="description-text">{{ $description }}</div>
            </div>
            @endif

            <div class="warning-note">
                <strong>Action Required:</strong> Please upload the penalty document to proceed.
            </div>
            
            <p style="text-align: center;">
                <a href="{{ $magicLink }}" class="action-button">
                    Upload Penalty Document
                </a>
            </p>
            
            <p style="color: #6b7280; font-size: 14px;">
                Click the button above to access the finance portal and upload the penalty document for this unit.
            </p>
        </div>
        
        <div class="footer">
            <p style="margin: 5px 0;">This is an automated notification from Zed Capital Booking System</p>
            <p style="margin: 5px 0;">© {{ date('Y') }} Zed Capital. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
