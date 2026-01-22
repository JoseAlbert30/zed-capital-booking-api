<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Utilities Registration Guide - Viera Residences</title>
    <style>
        @page {
            margin: 110px 40px 85px 40px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #111;
            line-height: 1.3;
        }

        .header {
            position: fixed;
            top: -85px;
            left: 0;
            right: 0;
            height: 75px;
        }

        .footer {
            position: fixed;
            bottom: -65px;
            left: 0;
            right: 0;
            height: 55px;
            color: #fff;
        }

        .footer-bar {
            background: #111;
            height: 55px;
            padding: 10px 14px;
        }

        .brand-row {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-row td {
            vertical-align: middle;
        }

        .title-bar {
            background: #111;
            color: #fff;
            padding: 8px 14px;
            font-weight: 500;
            font-size: 10pt;
            text-transform: uppercase;
        }

        .muted {
            color: #444;
        }

        .small {
            font-size: 9pt;
        }

        .right {
            text-align: right;
        }

        .spacer-8 {
            height: 8px;
        }

        h1 {
            font-size: 16pt;
            font-weight: 600;
            margin: 0 0 10px 0;
            color: #111;
        }

        h2 {
            font-size: 14pt;
            font-weight: 600;
            margin: 20px 0 10px 0;
            color: #111;
        }

        h3 {
            font-size: 12pt;
            font-weight: 600;
            margin: 15px 0 8px 0;
            color: #111;
        }

        p {
            margin: 0 0 12px 0;
        }

        .intro {
            font-size: 10pt;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .section {
            margin-bottom: 8px;
        }

        ul {
            margin: 8px 0;
            padding-left: 25px;
        }

        li {
            margin-bottom: 6px;
        }

        .highlight {
            background-color: #fff9c4;
            padding: 2px 4px;
            font-weight: 600;
        }

        .contact-box {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 12px;
            margin: 15px 0;
        }

        .contact-box p {
            margin: 4px 0;
            font-size: 9pt;
        }

        .optional-box {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 12px;
            margin: 10px 0;
        }

        .optional-box h3 {
            margin-top: 0;
            color: #2e7d32;
        }

        strong {
            font-weight: 600;
        }
    </style>
</head>

<body>

    {{-- ===================== HEADER / FOOTER (ALL PAGES) ===================== --}}
    <div class="header">
        <table class="brand-row">
            <tr>
                <td style="width: 33%;">
                    {{-- Left logo --}}
                    @if(!empty($logos['left']))
                    <img src="{{ $logos['left'] }}" style="max-width: 80px; height: auto;">
                    @else
                    <div class="muted small"><strong>VIERA RESIDENCES</strong></div>
                    @endif
                </td>
                <td style="width: 34%;"></td>
                <td style="width: 33%;" class="right">
                    {{-- Right logo --}}
                    @if(!empty($logos['right']))
                    <img src="{{ $logos['right'] }}" style="max-width: 120px; height: auto;">
                    @else
                    <div class="muted small"><strong>VANTAGE VENTURES</strong></div>
                    @endif
                </td>
            </tr>
        </table>
        <div class="spacer-8"></div>
    </div>

    <div class="footer">
        <div class="footer-bar">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="width:70%; font-size:8pt; color:#fff;">
                        Office 12F-A-04, Empire Heights A, Business Bay, Dubai, UAE.<br>
                        +971 58 898 0456 | vantage@zedcapital.ae | vieraresidences.ae
                    </td>
                    <td style="width:30%; text-align:right; font-size:8pt; color:#fff;">
                        <div class="small">Powered By</div>
                        <div style="font-weight:500;">ZED CAPITAL</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ===================== MAIN CONTENT ===================== --}}
    
    <h1>UTILITIES REGISTRATION GUIDE</h1>
    <p style="font-size: 11pt; font-weight: 500; margin-bottom: 5px;">Viera Residences</p>
    
    <p class="intro">
        This guide outlines the steps required to complete your DEWA and Chilled Water / AC (Zenner) 
        registrations, which are <strong>mandatory prior to handover</strong>.
    </p>

    {{-- ===================== DEWA SECTION ===================== --}}
    <div class="section">
        <h2>1. DEWA REGISTRATION (Electricity & Water)</h2>
        
        <p>
            Dubai Electricity and Water Authority (DEWA) provides electricity, water, and sewerage 
            services across Dubai. As original connections were completed during construction, your 
            application will be treated as a <strong>reconnection</strong>.
        </p>

        <h3>Steps and Required Documents:</h3>
        <ul>
            <li>Register online at: <strong>www.dewa.gov.ae</strong></li>
            <li>Complete the DEWA application form</li>
            <li>Upload owner's passport</li>
            <li>Upload Emirates ID (if applicable)</li>
            <li>Provide DEWA premise number: <span class="highlight">{{ $dewaPremiseNumber ?? '[Premise Number Not Provided]' }}</span></li>
            <li>Proof of ownership (Oqood)</li>
            <li>Payment of the required deposit</li>
            <li>Download or save the DEWA confirmation receipt</li>
        </ul>
    </div>

    {{-- ===================== ZENNER SECTION ===================== --}}
    <div class="section">
        <h2>2. CHILLED WATER / AC REGISTRATION (ZENNER)</h2>
        
        <ul>
            <li>Register online at: <strong>https://myzenner.ae/Registration</strong></li>
            <li>Upon document verification, a payment link will be sent for the activation fee and 
                security deposit payments (amount varies by unit type)</li>
        </ul>

        <div class="contact-box">
            <p><strong>Zenner Contact Details:</strong></p>
            <p>Phone: <strong>04 333 7788</strong> (Mon–Fri, 08:30–17:30)</p>
            <p>Toll-Free (Out of Office): <strong>800-ZENNER (936637)</strong></p>
            <p>WhatsApp (Urgent Requests): <strong>+971 56 404 7391</strong> (Mon–Fri, 08:30–17:30)</p>
        </div>
    </div>

    {{-- ===================== OPTIONAL ASSISTANCE ===================== --}}
    <div class="optional-box">
        <h3>Optional Assistance</h3>
        <p>
            Should you wish our Property Management team to assist with utility registrations, 
            additional service charges will apply.
        </p>
        <p style="margin-top: 10px;">
            <strong>Phone:</strong> 058 589 0456<br>
            <strong>Email:</strong> pm@zedcapital.ae
        </p>
    </div>

</body>

</html>
