<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Handover Appointment Confirmation</title>
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
        .appointment-box {
            background-color: #f5f5f5;
            padding: 20px;
            border-left: 4px solid #0066cc;
            margin: 20px 0;
            border-radius: 5px;
        }
        .appointment-box p {
            margin: 5px 0;
            font-size: 16px;
        }
        .appointment-box .date {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 10px;
        }
        .appointment-box .time {
            font-size: 20px;
            font-weight: bold;
            color: #231e15;
        }
        .info-box {
            background-color: #fff9e6;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffcc00;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
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
        .footer a {
            color: #ffffff;
            text-decoration: underline;
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

        <div class="content">
            <p>Dear {{ $firstName }},</p>

            <p>We are pleased to confirm your handover appointment for your unit at Viera Residences, scheduled as follows:</p>

            <p><strong>Unit Number:</strong> {{ $unitNumber ?? '[Insert Unit Number]' }}</p>
            <p><strong>Date:</strong> {{ $appointmentDate }}</p>
            <p><strong>Time:</strong> {{ $appointmentTime }}</p>
            <p><strong>Location:</strong> Viera Residences, {{ $locationPin ?? 'Dubai Land, Dubai' }}</p>

            <hr style="border: 0; border-top: 1px solid #d6d6d6; margin: 20px 0;">

            <h2 style="color: #333; font-size: 18px; margin: 20px 0 10px 0;">Site Access & Parking Instructions</h2>
            <ul>
                <li>Please arrive 10 minutes prior to your scheduled appointment time.</li>
                <li>Parking available in the building, inform security that you are attending a handover appointment.</li>
                <li>Upon arrival, kindly proceed to the Viera Residences main entrance / reception and inform our team that you are attending a handover appointment.</li>
                <li>A valid original Emirates ID or passport is required for site access.</li>
            </ul>

            <p>If a representative is attending on your behalf, please ensure they carry a valid original Power of Attorney (PoA).</p>
            <p><br/></p>

            <hr style="border: 0; border-top: 1px solid #d6d6d6; margin: 20px 0;">

            <h2 style="color: #333; font-size: 18px; margin: 20px 0 10px 0;">Handover Officer Executive</h2>
            <p>Your handover will be conducted by our appointed Handover Executive:</p>

            <p><strong>Name:</strong> Mohamad</p>
            <p><strong>Mobile:</strong> +971 52 273 1458</p>

            <p>Please contact the Handover Executive directly in case of delay or difficulty locating the site on the day of your appointment.</p>

            <hr style="border: 0; border-top: 1px solid #d6d6d6; margin: 20px 0;">

            <h2 style="color: #333; font-size: 18px; margin: 20px 0 10px 0;">Important Notes</h2>
            <ul>
                <li>The appointment includes unit, amenities and parking inspection and key handover.</li>
                <li>Please bring your original Emirates ID or passport for verification.</li>
                <li>If applicable, please bring Power of Attorney documents.</li>
                <li>If you need to reschedule, please contact us at least 24 hours in advance.</li>
            </ul>

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
