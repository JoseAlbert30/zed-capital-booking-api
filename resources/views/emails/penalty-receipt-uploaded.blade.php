<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penalty Receipt Uploaded by Developer</title>
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
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
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
            background-color: #eef2ff;
            border-left: 4px solid #6366f1;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .detail-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #ddd6fe;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #3730a3;
            min-width: 140px;
        }
        .detail-value {
            color: #1f2937;
            flex: 1;
        }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .action-button:hover {
            transform: translateY(-2px);
        }
        .instructions {
            background-color: #dbeafe;
            border: 1px solid #60a5fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .instructions h3 {
            color: #1e40af;
            margin-top: 0;
            font-size: 16px;
        }
        .instructions p {
            margin: 10px 0;
            color: #1e3a8a;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>🧾 Penalty Receipt Uploaded</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hello Admin Team,
            </div>

            <p>The developer has uploaded a <strong>receipt</strong> for the following penalty:</p>

            <!-- Penalty Details -->
            <div class="penalty-details">
                <div class="detail-row">
                    <div class="detail-label">Penalty Number:</div>
                    <div class="detail-value">{{ $penaltyNumber }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Penalty Name:</div>
                    <div class="detail-value">{{ $penaltyName }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Unit Number:</div>
                    <div class="detail-value">{{ $unitNumber }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Project:</div>
                    <div class="detail-value">{{ $projectName }}</div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="instructions">
                <h3>📨 Next Action Required</h3>
                <p><strong>You can now send this receipt to the buyer.</strong></p>
                <p>Please review the receipt and forward it to the unit buyer through the Finance Portal.</p>
            </div>

            <!-- Action Button -->
            <div style="text-align: center;">
                <a href="{{ env('FRONTEND_URL', 'http://localhost:3000') }}/admin/finance" class="action-button">
                    View in Finance Portal
                </a>
            </div>

            <p style="margin-top: 20px; color: #6b7280; font-size: 14px;">
                You can download the receipt from the penalty details page.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0;">
                <strong>Zed Capital Booking System</strong><br>
                This is an automated notification. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
