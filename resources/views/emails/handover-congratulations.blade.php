<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Congratulations - Handover Complete</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, Verdana, sans-serif;
            line-height: 1.6;
            color: #231e15;
            margin: 0;
            padding: 0;
            background-color: #e4e4e4;
        }
        .container {
            max-width: 660px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            padding: 12px 24px;
            text-align: center;
        }
        .header img {
            max-width: 100%;
            height: auto;
        }
        .content {
            padding: 10px 45px 50px;
        }
        h1, h2 {
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        h2 {
            font-size: 18px;
            color: #333;
        }
        p {
            margin: 10px 0;
            font-size: 16px;
        }
        strong {
            font-weight: bold;
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        li {
            margin: 5px 0;
            font-size: 16px;
        }
        .footer {
            background-color: #231e15;
            padding: 24px;
            text-align: center;
        }
        .footer p {
            color: #ffffff;
            font-size: 12px;
            margin: 5px 0;
        }
        .logo {
            max-width: 150px;
            height: auto;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Logo -->
        <div class="header">
            <img src="https://mcusercontent.com/7f9a58b831a9399a5ad6a590b/images/b60c84d3-53b0-9d3e-8628-5a9b67bb660f.png" 
                 alt="Viera Residences" 
                 style="max-width: 100%; height: auto;">
        </div>

        <!-- Content -->
        <div class="content">
            <p>Dear {{ $userName }},</p>

            <p><strong>Congratulations on the successful completion of your unit handover!</strong></p>

            <p>We are delighted to inform you that your unit handover has been completed successfully. This is an exciting milestone, and we're thrilled to be part of your journey to homeownership.</p>

            <h2 style="color: #333; font-size: 18px; margin: 20px 0 10px 0;">Your Property Details</h2>
            <p><strong>Project:</strong> {{ $projectName }}</p>
            <p><strong>Unit:</strong> {{ $unitName }}</p>

            <p>Your new home is now ready for you to move in and start creating wonderful memories. All the necessary documentation has been processed, and the keys are officially yours!</p>

            <h2 style="color: #333; font-size: 18px; margin: 20px 0 10px 0;">What's Next?</h2>
            <ul>
                <li>You can now access your unit at any time</li>
                <li>All handover documents are available in your account</li>
                <li>If you have any questions or need assistance, our team is here to help</li>
            </ul>

            <p>If you have any questions or require assistance, please feel free to contact us at <a href="mailto:vantage@zedcapital.ae" style="color: #0066cc; text-decoration: none;"><strong>vantage@zedcapital.ae</strong></a></p>

            <p>Welcome to your new home! We wish you all the happiness and success in this new chapter.</p>

            <p>Yours sincerely,<br/>
            <strong>Vantage Ventures Real Estate Development L.L.C</strong><br/>
            <em>Powered by Zed Capital</em></p>

            <img src="https://mcusercontent.com/7f9a58b831a9399a5ad6a590b/images/169e75cc-cee5-9ff4-eed8-977ac449df2c.png" 
                 class="logo" 
                 alt="Company Logo">
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><em>Copyright (C) 2025 Zed Capital Real Estate. All rights reserved.</em></p>
            <p>Want to change how you receive these emails?</p>
            <p>You can update your preferences or unsubscribe from this list.</p>
        </div>
    </div>
</body>
</html>
