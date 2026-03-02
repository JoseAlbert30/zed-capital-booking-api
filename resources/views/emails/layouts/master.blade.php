<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Zed Capital' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            display: none;
        }
        
        .logo {
            display: none;
        }
        
        .header-title {
            display: none;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 18px;
            color: #1a1a1a;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message-body {
            color: #333333;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 30px;
        }
        
        .info-box {
            background-color: #f8f8f8;
            border-left: 4px solid #1a1a1a;
            padding: 20px;
            margin: 25px 0;
        }
        
        .info-box h3 {
            color: #1a1a1a;
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666666;
            flex: 1;
        }
        
        .info-value {
            color: #1a1a1a;
            font-weight: 500;
            flex: 1;
            text-align: right;
        }
        
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        
        .button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #cccccc, transparent);
            margin: 30px 0;
        }
        
        .note-box {
            background-color: #fafafa;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .note-box h4 {
            color: #1a1a1a;
            font-size: 14px;
            margin-bottom: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .note-box ul {
            list-style: none;
            padding-left: 0;
        }
        
        .note-box li {
            color: #555555;
            font-size: 14px;
            padding: 6px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .note-box li:before {
            content: "▪";
            position: absolute;
            left: 0;
            color: #1a1a1a;
            font-weight: bold;
        }
        
        .footer {
            background-color: #1a1a1a;
            color: #ffffff;
            padding: 30px;
            text-align: center;
            font-size: 13px;
        }
        
        .footer-text {
            color: #cccccc;
            line-height: 1.8;
        }
        
        .footer-link {
            color: #ffffff;
            text-decoration: underline;
        }
        
        .signature {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666666;
            font-size: 14px;
        }
        
        .signature strong {
            color: #1a1a1a;
            display: block;
            margin-bottom: 5px;
            font-size: 15px;
        }
        
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .footer {
                padding: 20px;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-value {
                text-align: left;
                margin-top: 5px;
            }
            
            .button {
                padding: 14px 30px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- Header with Logo -->
        <div class="header">
            <img src="https://mcusercontent.com/7f9a58b831a9399a5ad6a590b/images/5e46cd16-0b59-96ba-e942-5b4e0fba9f4d.png" alt="Zed Capital" class="logo">
            <div class="header-title">{{ $headerTitle ?? 'Zed Capital' }}</div>
        </div>
        
        <!-- Main Content -->
        <div class="content">
            @yield('content')
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                <strong>Zed Capital Real Estate</strong><br>
                Dubai, United Arab Emirates<br>
                <a href="https://zedcapital.ae" class="footer-link">www.zedcapital.ae</a><br><br>
                © {{ date('Y') }} Zed Capital. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
