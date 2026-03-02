<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penalty Proof of Payment Uploaded</title>
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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .detail-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #fef3c7;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #92400e;
            min-width: 140px;
        }
        .detail-value {
            color: #1f2937;
            flex: 1;
        }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
            background-color: #fef3c7;
            border: 1px solid #fbbf24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .instructions h3 {
            color: #92400e;
            margin-top: 0;
            font-size: 16px;
        }
        .instructions ul {
            margin: 10px 0;
            padding-left: 20px;
            color: #78350f;
        }
        .instructions li {
            margin: 8px 0;
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
            <h1>💳 Proof of Payment Uploaded</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hello {{ $developerName }},
            </div>

            <p>The admin team has uploaded the <strong>proof of payment</strong> for the following penalty:</p>

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
                <h3>📋 Next Steps Required</h3>
                <p><strong>You are now required to upload the receipt for this penalty.</strong></p>
                <ul>
                    <li>Log in to the Zed Capital Finance Portal</li>
                    <li>Navigate to the Penalties section</li>
                    <li>Find penalty <strong>{{ $penaltyNumber }}</strong></li>
                    <li>Click the "Upload Receipt" button</li>
                    <li>Upload the receipt document (PDF, JPG, or PNG)</li>
                </ul>
                <p style="margin-top: 15px; color: #92400e;">
                    <strong>⚠️ Important:</strong> Please upload the receipt as soon as possible so the admin can forward it to the buyer.
                </p>
            </div>

            <!-- Action Button -->
            <div style="text-align: center;">
                <a href="{{ $magicLink }}" class="action-button">
                    Access Finance Portal
                </a>
            </div>

            <p style="margin-top: 30px; color: #6b7280; font-size: 14px;">
                If you have any questions or concerns, please contact the Zed Capital admin team.
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
