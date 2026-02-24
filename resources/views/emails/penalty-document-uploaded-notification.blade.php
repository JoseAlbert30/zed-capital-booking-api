<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penalty Document Uploaded</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .detail-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #d1fae5;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #065f46;
            min-width: 140px;
        }
        .detail-value {
            color: #1f2937;
            flex: 1;
        }
        .amount-highlight {
            font-size: 20px;
            font-weight: 700;
            color: #065f46;
        }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }
        .action-button:hover {
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4);
        }
        .success-badge {
            background-color: #d1fae5;
            color: #065f46;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin: 10px 0;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .info-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>✅ Penalty Document Uploaded</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                Dear Admin Team,
            </div>
            
            <div class="success-badge">
                📄 Document Received
            </div>
            
            <p>The developer <strong>{{ $developerName }}</strong> has uploaded a penalty document for <strong>Unit {{ $unitNumber }}</strong>.</p>
            
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
                <div class="detail-row">
                    <span class="detail-label">Uploaded By:</span>
                    <span class="detail-value">{{ $developerName }}</span>
                </div>
            </div>

            <div class="info-box">
                <strong>📎 Document Available:</strong> The penalty document has been uploaded and is ready for review in the finance portal.
            </div>
            
            <p style="text-align: center;">
                <a href="{{ $documentUrl }}" class="action-button">
                    View Document
                </a>
            </p>
            
            <p style="color: #6b7280; font-size: 14px; text-align: center;">
                You can also access this document through the finance kanban board.
            </p>
        </div>
        
        <div class="footer">
            <p style="margin: 5px 0;">This is an automated notification from Zed Capital Booking System</p>
            <p style="margin: 5px 0;">© {{ date('Y') }} Zed Capital. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
