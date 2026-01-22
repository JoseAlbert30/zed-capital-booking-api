<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Handover Notice</title>
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

        ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        li {
            margin: 5px 0;
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

        .soa-link {
            color: #0066cc;
            text-decoration: underline;
            font-weight: bold;
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

            <p>We are pleased to inform you that your unit at Viera Residences is now ready for final handover following the issuance of the Building Completion Certificate by the Dubai Development Authority.</p>

            <p>This letter serves as the <strong>official Handover Notice</strong>. Kindly review and complete the steps outlined below to proceed with the handover process.</p>
        </div>

        <div class="divider"></div>

        <!-- Main Content Sections -->
        <div class="content">
            <h1>1. Final Payment</h1>
            <p>Kindly arrange settlement of the final amount due in accordance with the Sale and Purchase Agreement within <strong>30 calendar days</strong> from the date of this notice.</p>

            <p><strong>Your Statement of Account is attached to this email and can also be <a href="{{ $soaUrl }}" class="soa-link">viewed online here</a>.</strong></p>
            <p>Please find the escrow account details attached or <a href="{{ url('storage/handover-notice-attachments/viera-residences/Viera Residences - Escrow Acc.pdf') }}" class="soa-link">click here to download the escrow account details</a>. Email the proof of payment to <strong>finance@zedcapital.ae</strong>.</p>

            <p><br /></p>
            <h1>2. Utilities Connections, Registrations &amp; Service Charge</h1>
            <p>To proceed with the handover, please complete the <strong>DEWA</strong> and <strong>Chilled Water / AC (Zenner)</strong> registrations as per the steps mentioned in the <strong>attached guidelines PDF</strong> or <a href="{{ url('storage/attachments/' . $unit->property->project_name . '/' . $unit->unit . '/Utilities_Registration_Guide_Unit_' . $unit->unit . '.pdf') }}" class="soa-link">click here to download the utilities registration guide</a>.</p>

            <p>Once completed, kindly submit the following documents to <strong>vantage@zedcapital.ae</strong>:</p>
            <ul>
                <li>DEWA receipt showing the <strong>premise number</strong> for your unit</li>
                <li>Zenner receipt for chilled water / AC connection</li>
                <li>Signed copy of the attached <strong>Service Charge Undertaking Letter</strong></li>
            </ul>
            <p><br /></p>
            <h1>3. Handover Appointment</h1>
            <p>Once your payments have been settled and all utility registrations have been completed, our team will contact you to arrange the unit inspection and key handover, either with you or with your officially authorized representative (a valid Power of Attorney will be required).</p>
            <p><br /></p>
            <p><strong>Important Notice:</strong></p>
            <p>Please note that all responsibilities and risks associated with the unit shall transfer to the purchaser on the Handover Notification Date, irrespective of physical possession.</p>

            <p>For any queries, please contact us at <strong>vantage@zedcapital.ae</strong>.</p>

            <p>We look forward to welcoming you to your new home at <strong>Viera Residences</strong>.</p>

            <p>Warm regards,<br />
                <strong>Vantage Ventures Real Estate Development L.L.C.</strong>
            </p>

            <img src="https://mcusercontent.com/7f9a58b831a9399a5ad6a590b/images/169e75cc-cee5-9ff4-eed8-977ac449df2c.png"
                class="logo"
                alt="Company Logo">
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><br /></p>
            <p><em>Copyright (C) 2025 Zed Capital Real Estate. All rights reserved.</em></p>
            <p><br /></p>
            <p>Want to change how you receive these emails?</p>
            <p>You can update your preferences or unsubscribe from this list.</p>
        </div>
    </div>
</body>

</html>