<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>No Objection Certificate - Unit Handover</title>
    <style>
        @page {
            margin: 110px 40px 85px 40px;
        }

        body {
            font-family: Poppins, Arial, sans-serif;
            font-size: 11pt;
            color: #111;
            line-height: 1.6;
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

        .date {
            text-align: left;
            margin-bottom: 30px;
        }

        .subject {
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
            text-decoration: underline;
        }

        .content {
            text-align: justify;
            margin-bottom: 40px;
            line-height: 2;
        }

        .details {
            margin: 40px 0;
            line-height: 2.5;
        }

        .signature-section {
            margin-top: 100px;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            width: 300px;
            margin-top: 80px;
        }

        .company-name {
            font-weight: bold;
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

    <div class="date">
        <strong>Date:</strong> {{ $date }}
    </div>

    <div class="subject">
        Subject: NO OBJECTION CERTIFICATE FOR UNIT HANDOVER
    </div>

    <div class="content">
        <p>
            This is to confirm that <strong>Vantage Ventures Real Estate Development L.L.C.</strong>, as the Developer of <strong>Viera Residences</strong>, has no objection to the handover of the Unit to the Buyer or the Buyer's authorized legal representative, as all payment obligations under the Sale & Purchase Agreement have been fully satisfied.
        </p>
    </div>

    <div class="details">
        <p><strong>Unit Number:</strong> {{ $unit_number }}</p>
        <p><strong>Buyer 1:</strong> {{ $buyer1_name }}</p>
        @if(!empty($buyer2_name))
        <p><strong>Buyer 2:</strong> {{ $buyer2_name }}</p>
        @endif
    </div>

    <div class="signature-section">
        <div class="signature-line"></div>
        <p class="company-name">For Vantage Ventures Real Estate Development L.L.C</p>
        <p>(signature & stamp)</p>
    </div>

</body>
</html>
