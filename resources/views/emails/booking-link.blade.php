<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Booking Platform Access</title>
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
        .divider {
            border-top: 1px solid #d6d6d6;
            margin: 12px 24px;
        }
        h1 {
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        p {
            margin: 10px 0;
            font-size: 16px;
        }
        strong {
            font-weight: bold;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            background-color: #0066cc;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #0052a3;
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
        .footer a {
            color: #ffffff;
            text-decoration: underline;
        }
        .logo {
            max-width: 150px;
            height: auto;
            margin-top: 20px;
        }
        .link-box {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            word-break: break-all;
            font-size: 14px;
            color: #666;
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
            <p>Dear {{ $firstName }},</p>
            
            <p>Thank you for completing all the required formalities in relation to your unit at Viera Residences.</p>
            
            <p>You may now proceed to schedule your unit inspection and handover appointment at your convenience.</p>
            
            <h2 style="color: #333; font-size: 18px; margin: 20px 0 10px 0;">Book Your Handover Appointment</h2>
            
            <p>Please select your preferred date and time using the calendar link below:</p>

            <div style="text-align: center; margin: 25px 0;">
                <a href="{{ $bookingUrl }}" style="display: inline-block; padding: 14px 30px; background-color: #000000; color: #ffffff; text-decoration: none; font-weight: 600; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">Click here to book your handover appointment</a>
            </div>

            <p>Or copy and paste this link into your browser:</p>
            <div class="link-box">{{ $bookingUrl }}</div>

            <p><strong>Important:</strong> Please bring the following documents on the day of handover:</p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Original Emirates ID or passport</li>
                <li>Power of Attorney (if applicable)</li>
            </ul>

            <p>If you have any questions or require assistance, please feel free to contact us at <a href="mailto:vantage@zedcapital.ae" style="color: #0066cc; text-decoration: none;"><strong>vantage@zedcapital.ae</strong></a></p>

            <p>We look forward to welcoming you and handing over your new home at Viera Residences.</p>

            <p>Yours sincerely,<br/>
            <strong>Vantage Ventures Real Estate Development L.L.C</strong><br/>
            <em>Powered by Zed Capital</em></p>

            <img src="https://mcusercontent.com/7f9a58b831a9399a5ad6a590b/images/169e75cc-cee5-9ff4-eed8-977ac449df2c.png" 
                 class="logo" 
                 alt="Company Logo">
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><br/></p>
            <p><em>Copyright (C) 2025 Zed Capital Real Estate. All rights reserved.</em></p>
            <p><br/></p>
            <p>Want to change how you receive these emails?</p>
            <p>You can update your preferences or unsubscribe from this list.</p>
        </div>
    </div>
</body>
</html>
