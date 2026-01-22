<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Statement of Account</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 40px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #0066cc;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #0066cc;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin: 30px 0;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        .owners-list {
            margin-top: 10px;
        }
        .owner-item {
            padding: 8px 0;
            padding-left: 20px;
            border-left: 3px solid #0066cc;
            margin: 5px 0;
            background: #f9f9f9;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #e0e0e0;
            padding-top: 20px;
        }
        .placeholder {
            margin: 30px 0;
            padding: 20px;
            background: #f5f5f5;
            border: 2px dashed #ccc;
            text-align: center;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>STATEMENT OF ACCOUNT</h1>
        <p>{{ $property }}</p>
        <p>Generated: {{ $generatedDate }}</p>
    </div>

    <div class="section">
        <div class="section-title">Unit Information</div>
        <div class="info-row">
            <span class="info-label">Unit Number:</span>
            <span class="info-value">{{ $unitNumber }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Owner(s)</div>
        <div class="owners-list">
            @foreach($owners as $owner)
                <div class="owner-item">{{ $owner }}</div>
            @endforeach
        </div>
    </div>

    <div class="placeholder">
        Additional SOA details will be added here in future updates
    </div>

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
        <p>For inquiries, please contact finance@zedcapital.ae</p>
    </div>
</body>
</html>
