<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #ef4444;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
        }
        .info-table {
            width: 100%;
            margin: 20px 0;
        }
        .info-table td {
            padding: 8px;
            border-bottom: 1px solid #e5e5e5;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 40%;
        }
        .button {
            display: inline-block;
            padding: 14px 28px;
            background-color: #ef4444;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #dc2626;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666666;
        }
        .warning-badge {
            display: inline-block;
            background-color: #ef4444;
            color: white;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Penalty Notice</h1>
        </div>
        
        <div class="content">
            <div style="text-align: center;">
                <span class="warning-badge">! IMPORTANT NOTICE</span>
            </div>
            
            <p class="greeting">Dear {{ $buyerName }},</p>
            
            <p>A penalty has been issued for your unit. Please review the details below:</p>
            
            <div class="info-box">
                <table class="info-table">
                    <tr>
                        <td>Penalty Number:</td>
                        <td><strong>{{ $penaltyNumber }}</strong></td>
                    </tr>
                    <tr>
                        <td>Penalty Name:</td>
                        <td><strong>{{ $penaltyName }}</strong></td>
                    </tr>
                    <tr>
                        <td>Unit Number:</td>
                        <td><strong>{{ $unitNumber }}</strong></td>
                    </tr>
                    <tr>
                        <td>Project:</td>
                        <td><strong>{{ $projectName }}</strong></td>
                    </tr>
                    <tr>
                        <td>Date:</td>
                        <td><strong>{{ date('F j, Y') }}</strong></td>
                    </tr>
                </table>
            </div>

            <div style="text-align: center;">
                <a href="{{ $documentUrl }}" class="button">View Penalty Document</a>
            </div>
            
            <p style="margin-top: 20px;">Please review the document carefully and contact our finance department if you have any questions or concerns.</p>
            
            <p><strong>Important:</strong> Please address this matter promptly to avoid any further complications.</p>
            
            <p>Best regards,<br>
            <strong>Zed Capital Finance Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from Zed Capital Booking System</p>
            <p>Finance: finance@zedcapital.ae | Support: support@zedcapital.ae</p>
            <p>&copy; {{ date('Y') }} Zed Capital. All rights reserved.</p>
        </div>
    </div>
</body>
</html>