<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Handover Appointment Rescheduled</title>
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
            <p>Dear {{ $firstName }},</p>

            <p>This is to inform you that your handover appointment for your unit at Viera Residences has been rescheduled.</p>

            <h2 style="color: #333; font-size: 18px; margin: 20px 0 10px 0;">Previous Appointment</h2>
            <p><strong>Date:</strong> {{ $oldAppointmentDate }}</p>
            <p><strong>Time:</strong> {{ $oldAppointmentTime }}</p>

            <hr style="border: 0; border-top: 1px solid #d6d6d6; margin: 20px 0;">

            <h2 style="color: #333; font-size: 18px; margin: 20px 0 10px 0;">New Appointment Details</h2>
            <p><strong>Date:</strong> {{ $newAppointmentDate }}</p>
            <p><strong>Time:</strong> {{ $newAppointmentTime }}</p>
            <p><strong>Location:</strong> Viera Residences, Dubai Land, Dubai</p>

            <hr style="border: 0; border-top: 1px solid #d6d6d6; margin: 20px 0;">

            <h2 style="color: #333; font-size: 18px; margin: 20px 0 10px 0;">Important Reminders</h2>
            <ul>
                <li>Please arrive 10 minutes prior to your scheduled appointment time.</li>
                <li>Bring your original Emirates ID or passport for verification.</li>
                <li>If a representative is attending on your behalf, ensure they carry a valid Power of Attorney.</li>
            </ul>

            <p>If you need to make further changes or have any questions, please contact us at <a href="mailto:vantage@zedcapital.ae" style="color: #0066cc; text-decoration: none;"><strong>vantage@zedcapital.ae</strong></a></p>

            <p>We look forward to welcoming you on your new appointment date.</p>

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
