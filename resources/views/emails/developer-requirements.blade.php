<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Handover Requirements - Developer Approval</title>
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
        h2 {
            color: #333;
            font-size: 18px;
            margin: 20px 0 10px 0;
        }
        p {
            margin: 10px 0;
            font-size: 16px;
        }
        strong {
            font-weight: bold;
        }
        strong {
            font-weight: bold;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .info-table td {
            padding: 8px;
            border-bottom: 1px solid #d6d6d6;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 40%;
        }
        .documents-list {
            background: #f5f5f5;
            border-left: 4px solid #0066cc;
            padding: 15px;
            margin-top: 10px;
            border-radius: 5px;
        }
        .document-item {
            padding: 10px;
            border-bottom: 1px solid #e4e4e4;
        }
        .document-item:last-child {
            border-bottom: none;
        }
        .highlight {
            background: #fff9e6;
            padding: 15px;
            border-left: 4px solid #ffcc00;
            margin: 20px 0;
            border-radius: 5px;
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
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        li {
            margin: 5px 0;
            font-size: 16px;
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
            <h1>Handover Requirements Ready for Approval</h1>
            
            <p>Dear Vantage Team,</p>
            
            <p>The buyer has completed all required handover documents for the following unit. Please review the attached documents and complete the developer requirements.</p>

            <hr style="border: 0; border-top: 1px solid #d6d6d6; margin: 20px 0;">

            <h2>Unit Information</h2>
            <table class="info-table">
                <tr>
                    <td>Unit Number:</td>
                    <td>{{ $unit->unit }}</td>
                </tr>
                <tr>
                    <td>Project:</td>
                    <td>{{ $unit->property->project_name }}</td>
                </tr>
                <tr>
                    <td>Location:</td>
                    <td>{{ $unit->property->location }}</td>
                </tr>
            </table>

            <hr style="border: 0; border-top: 1px solid #d6d6d6; margin: 20px 0;">

            <h2>Owner Information</h2>
            <table class="info-table">
                <tr>
                    <td>Name:</td>
                    <td>{{ $owner->full_name }}</td>
                </tr>
                <tr>
                    <td>Email:</td>
                    <td>{{ $owner->email }}</td>
                </tr>
                <tr>
                    <td>Phone:</td>
                    <td>{{ $owner->mobile_number }}</td>
                </tr>
            </table>

            <div class="highlight">
                <p><strong>Action Required:</strong> The buyer has completed all required handover documents. Please review the documents below, sign the required forms, and upload them to complete the handover process.</p>
            </div>

            <hr style="border: 0; border-top: 1px solid #d6d6d6; margin: 20px 0;">

            <h2>Submitted Documents (Buyer Requirements)</h2>
            <div class="documents-list">
                @foreach($documents as $doc)
                <div class="document-item">
                    <strong>
                        @if($doc->type === 'payment_proof')
                            100% SOA Receipt
                        @elseif($doc->type === 'ac_connection')
                            AC Connection
                        @elseif($doc->type === 'dewa_connection')
                            DEWA Connection
                        @elseif($doc->type === 'service_charge_ack_buyer')
                            Service Charge Acknowledgement (Signed by Buyer)
                        @elseif($doc->type === 'bank_noc')
                            Bank NOC
                        @else
                            {{ ucfirst(str_replace('_', ' ', $doc->type)) }}
                        @endif
                    </strong>
                    <br>
                    <small style="color: #666;">{{ $doc->filename }}</small>
                </div>
                @endforeach
            </div>

            <hr style="border: 0; border-top: 1px solid #d6d6d6; margin: 20px 0;">

            <h2>Next Steps</h2>
            <ol>
                <li>Review all submitted buyer documents (attached to this email)</li>
                <li>Download and sign the Service Charge Acknowledgement</li>
                <li>Download and sign the Developer NOC (No Objection Certificate) - <strong>attached to this email</strong></li>
                <li>Upload both signed documents to the admin portal</li>
                <li>System will notify the buyer once all documents are complete</li>
            </ol>
            <p style="margin-top: 15px; padding: 10px; background: #fff9e6; border-left: 4px solid #ffcc00;">
                <strong>Note:</strong> All buyer documents and the NOC template are attached to this email for your convenience.
            </p>

            <p style="margin-top: 20px;">We look forward to your prompt response to complete the handover process.</p>

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
            <p>This is an automated notification from the Viera Residences Handover Management System.</p>
        </div>
    </div>
</body>
</html>
