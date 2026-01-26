<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Statement of Account - Viera Residences</title>
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
        p {
            margin: 10px 0;
            font-size: 16px;
        }
        strong {
            font-weight: bold;
        }
        .bank-details {
            background-color: #f5f5f5;
            border-left: 4px solid #0066cc;
            padding: 15px;
            margin: 20px 0;
        }
        .bank-details h3 {
            margin-top: 0;
            color: #0066cc;
            font-size: 18px;
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
        
            <p>Referring to our email earlier with regards to the issuance of Viera Residences Building Completion Certificate (BCC), please find attached your Statement of Account (SOA) summary, which outlines the payments received to date and the outstanding balance for your property <strong>{{ $unitNumber }}</strong>.</p>
            
            <p>We kindly request you to review the attached SOA and proceed with the settlement of the remaining balance.</p>
            
            <p><strong>Please ensure that the Unit Number is mentioned in the remarks section for each fund transfer.</strong></p>
            
            <p>Before making any bank transfer, always verify the bank details stated in your Sale and Purchase Agreement (SPA) to ensure accuracy and prevent errors. The Purchaser acknowledges that any transfer made to an incorrect account, due to failure to verify such details, shall be at the Purchaser's sole risk and responsibility.</p>
            
            <div class="bank-details">
                <h3>Escrow Account Details:</h3>
                <p style="margin: 5px 0;"><strong>Account Name:</strong> VIERA RESIDENCES</p>
                <p style="margin: 5px 0;"><strong>Bank Name:</strong> COMMERCIAL BANK INTERNATIONAL PJSC (CBI)</p>
                <p style="margin: 5px 0;"><strong>Account No.:</strong> 100110040083</p>
                <p style="margin: 5px 0;"><strong>IBAN NO.:</strong> AE740220000100110040083</p>
                <p style="margin: 5px 0;"><strong>SWIFT CODE:</strong> CLIBIAEADXXX</p>
                <p style="margin: 5px 0;"><strong>Address:</strong> Sharjah Buhaira Branch</p>
            </div>
            
            <p><em>Please e-mail the proof of payment to <a href="mailto:finance@zedcapital.ae" style="color: #0066cc; text-decoration: none;">finance@zedcapital.ae</a></em></p>
            
            <p>Should you have any questions or require clarification at this stage, please do not hesitate to contact our team. We remain committed to supporting you through the final steps of development.</p>
            
            <p style="font-size: 18px; color: #0066cc; font-weight: bold; margin-top: 30px;">Your new home will be ready very soon!</p>
            
            <p>If you have any questions or require assistance, please feel free to contact us at <a href="mailto:vantage@zedcapital.ae" style="color: #0066cc; text-decoration: none;"><strong>vantage@zedcapital.ae</strong></a></p>

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
