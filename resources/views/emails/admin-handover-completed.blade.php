<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Handover Completed - {{ $unitNumber }}</title>
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
        h1 {
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        h2 {
            font-size: 18px;
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
        .success-box {
            background-color: #d4edda;
            padding: 20px;
            border-left: 4px solid #28a745;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success-box h2 {
            color: #155724;
            margin-top: 0;
        }
        .info-section {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-section p {
            margin: 8px 0;
            font-size: 16px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            display: inline-block;
            width: 180px;
        }
        .attachments-box {
            background-color: #e7f3ff;
            padding: 20px;
            border-left: 4px solid #0066cc;
            margin: 20px 0;
            border-radius: 5px;
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

        <div class="content">
            <h1>✅ Handover Completed Successfully</h1>

            <div class="success-box">
                <h2>Handover Finalized</h2>
                <p>The handover process has been completed for Unit {{ $unitNumber }} at {{ $propertyName }}.</p>
            </div>

            <hr style="border: 0; border-top: 1px solid #d6d6d6; margin: 20px 0;">

            <h2>Handover Details</h2>
            <div class="info-section">
                <p><span class="info-label">Property:</span> {{ $propertyName }}</p>
                <p><span class="info-label">Unit Number:</span> {{ $unitNumber }}</p>
                <p><span class="info-label">Completion Date:</span> {{ $completionDate }}</p>
                <p><span class="info-label">Completion Time:</span> {{ $completionTime }}</p>
                <p><span class="info-label">Completed By:</span> {{ $completedBy }}</p>
            </div>

            <h2>Customer Information</h2>
            <div class="info-section">
                <p><span class="info-label">Primary Owner:</span> {{ $customerName }}</p>
                <p><span class="info-label">Email:</span> {{ $customerEmail }}</p>
                @if(isset($customerMobile))
                    <p><span class="info-label">Mobile:</span> {{ $customerMobile }}</p>
                @endif
                
                @if(isset($coOwners) && count($coOwners) > 0)
                    <p><span class="info-label">Co-Owners:</span></p>
                    <ul style="margin-left: 180px; margin-top: 5px;">
                        @foreach($coOwners as $coOwner)
                            <li>{{ $coOwner['name'] }} ({{ $coOwner['email'] }})</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <h2>Appointment Details</h2>
            <div class="info-section">
                <p><span class="info-label">Original Appointment:</span> {{ $appointmentDate }}</p>
                <p><span class="info-label">Appointment Time:</span> {{ $appointmentTime }}</p>
            </div>

            <div class="attachments-box">
                <h2 style="margin-top: 0;">📎 Attached Documents</h2>
                <p>The following handover documents are attached to this email:</p>
                <ul>
                    <li><strong>Declaration Document</strong> - Signed handover declaration</li>
                    <li><strong>Handover Checklist</strong> - Completed inspection checklist</li>
                    <li><strong>Handover Photo</strong> - Unit handover documentation photo</li>
                </ul>
            </div>

            <hr style="border: 0; border-top: 1px solid #d6d6d6; margin: 20px 0;">

            <p><strong>This is an automated notification from the Handover Management System.</strong></p>
            <p>All handover documentation has been generated and attached for your records.</p>

            <img src="https://mcusercontent.com/7f9a58b831a9399a5ad6a590b/images/169e75cc-cee5-9ff4-eed8-977ac449df2c.png" 
                 class="logo" 
                 alt="Company Logo">
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><br/></p>
            <p><em>Copyright (C) 2025 Zed Capital Real Estate. All rights reserved.</em></p>
            <p><br/></p>
        </div>
    </div>
</body>
</html>
