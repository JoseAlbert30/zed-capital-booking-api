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
            background-color: #f59e0b;
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
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
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
            background-color: #f59e0b;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #d97706;
        }
        .document-links {
            background-color: #f9fafb;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .document-link {
            display: block;
            color: #2563eb;
            text-decoration: none;
            padding: 8px 0;
        }
        .document-link:hover {
            text-decoration: underline;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666666;
        }
        .note {
            background-color: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 SOA Request - Action Required</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Dear {{ $developerName }},</p>
            
            <p>We are requesting the Statement of Account (SOA) for the following proof of payment:</p>
            
            <div class="info-box">
                <table class="info-table">
                    <tr>
                        <td>POP Number:</td>
                        <td><strong>{{ $popNumber }}</strong></td>
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
                        <td>Amount:</td>
                        <td><strong>AED {{ number_format($amount, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td>Receipt:</td>
                        <td><strong>{{ $receiptNumber }}</strong></td>
                    </tr>
                </table>
            </div>

            <div class="note">
                <strong>⚠️ Action Required:</strong> Please upload the Statement of Account (SOA) for this payment through the developer portal.
            </div>

            <div class="document-links">
                <p><strong>Reference Documents:</strong></p>
                <a href="{{ $popUrl }}" class="document-link">📎 View Proof of Payment</a>
                <a href="{{ $receiptUrl }}" class="document-link">🧾 View Receipt</a>
            </div>

            <div style="text-align: center;">
                <a href="{{ $magicLink }}" class="button">Access Developer Portal</a>
            </div>
            
            <p style="margin-top: 20px;">Click the button above to access the developer portal and upload the SOA document. This link will remain valid for 90 days.</p>
            
            <p>If you have any questions or need assistance, please contact our team.</p>
            
            <p>Best regards,<br>
            <strong>Zed Capital Finance Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from Zed Capital Booking System</p>
            <p>Operations: operations@zedcapital.ae | Docs: docs@zedcapital.ae</p>
            <p>&copy; {{ date('Y') }} Zed Capital. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
