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
            background-color: #7c3aed;
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
            background-color: #ede9fe;
            border-left: 4px solid #7c3aed;
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
            background-color: #7c3aed;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #6d28d9;
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
        .success-badge {
            display: inline-block;
            background-color: #10b981;
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
            <h1>📊 SOA Documents Uploaded</h1>
        </div>
        
        <div class="content">
            <div style="text-align: center;">
                <span class="success-badge">✓ NEW UPLOAD</span>
            </div>
            
            <p class="greeting">Dear Admin Team,</p>
            
            <p>The developer has uploaded the Statement of Account (SOA) documents for the following payment:</p>
            
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
                        <td>Uploaded By:</td>
                        <td><strong>{{ $developerName }}</strong></td>
                    </tr>
                    <tr>
                        <td>Upload Date:</td>
                        <td><strong>{{ date('F j, Y g:i A') }}</strong></td>
                    </tr>
                </table>
            </div>

            <div class="document-links">
                <p><strong>Document Links:</strong></p>
                <a href="{{ $soaUrl }}" class="document-link">📊 View SOA Documents (NEW)</a>
                <a href="{{ $popUrl }}" class="document-link">📎 View Proof of Payment</a>
                <a href="{{ $receiptUrl }}" class="document-link">🧾 View Receipt</a>
            </div>

            <div style="text-align: center;">
                <a href="{{ $soaUrl }}" class="button">Download SOA Documents</a>
            </div>
            
            <p style="margin-top: 20px;">Please review the uploaded documents and proceed with the next steps in the finance workflow.</p>
            
            <p>Best regards,<br>
            <strong>Zed Capital Booking System</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from Zed Capital Booking System</p>
            <p>Operations: operations@zedcapital.ae | Docs: docs@zedcapital.ae</p>
            <p>&copy; {{ date('Y') }} Zed Capital. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
