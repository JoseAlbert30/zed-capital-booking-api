<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Finance Clearance - Unit {{ $unit->unit }}</title>
    <style>
        @page {
            margin: 80px 40px 65px 40px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
            color: #111;
            line-height: 1.2;
        }

        .header {
            position: fixed;
            top: -65px;
            left: 0;
            right: 0;
            height: 60px;
        }

        .footer {
            position: fixed;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 40px;
            color: #fff;
        }

        .footer-bar {
            background: #111;
            height: 40px;
            padding: 6px 12px;
        }

        .brand-row {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-row td {
            vertical-align: middle;
        }

        .spacer-8 {
            height: 6px;
        }

        h1 {
            font-size: 13pt;
            font-weight: 700;
            margin: 0 0 6px 0;
            color: #111;
            text-transform: uppercase;
            text-align: center;
        }

        .section-title {
            font-size: 9.5pt;
            font-weight: 600;
            margin: 10px 0 6px 0;
            padding: 4px 6px;
            background-color: #f0f0f0;
            border-left: 3px solid #111;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }

        .info-table td {
            padding: 5px;
            border: 1px solid #ddd;
            font-size: 8.5pt;
        }

        .info-table td.label {
            background-color: #f5f5f5;
            font-weight: 600;
            width: 45%;
        }

        .info-table td.value {
            background-color: #fff;
            width: 55%;
        }

        .payment-summary {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }

        .payment-summary td {
            padding: 5px;
            border: 1px solid #ddd;
            font-size: 8.5pt;
        }

        .payment-summary td.label {
            background-color: #f5f5f5;
            font-weight: 600;
            width: 60%;
        }

        .payment-summary td.amount {
            background-color: #fff;
            width: 40%;
            text-align: right;
            font-weight: 600;
        }

        .payment-summary tr.highlight td {
            background-color: #e8f4f8;
            font-weight: 700;
        }

        .requirements-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }

        .requirements-table th {
            background-color: #333;
            color: #fff;
            border: 1px solid #111;
            padding: 5px;
            text-align: left;
            font-weight: 600;
            font-size: 8.5pt;
        }

        .requirements-table td {
            border: 1px solid #ddd;
            padding: 5px;
            font-size: 8.5pt;
        }

        .requirements-table td.requirement-label {
            background-color: #fafafa;
            width: 80%;
        }

        .requirements-table td.requirement-value {
            background-color: #fff;
            width: 20%;
            text-align: center;
            font-weight: 600;
        }

        .requirements-table td.requirement-value.yes {
            color: #28a745;
        }

        .requirements-table td.requirement-value.nil {
            color: #6c757d;
        }

        .requirements-table td.requirement-value.no {
            color: #dc3545;
        }

        .remarks-box {
            background-color: #fffbf0;
            border: 2px solid #ffc107;
            padding: 8px;
            margin: 6px 0;
        }

        .remarks-title {
            font-weight: 700;
            font-size: 9pt;
            margin-bottom: 4px;
            color: #856404;
        }

        .remarks-text {
            font-size: 8.5pt;
            color: #333;
            line-height: 1.3;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
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
                    <div style="color: #666; font-size: 9pt;"><strong>{{ strtoupper($property->project_name ?? 'VIERA RESIDENCES') }}</strong></div>
                    @endif
                </td>
                <td style="width: 34%;"></td>
                <td style="width: 33%;" class="text-right">
                    {{-- Right logo --}}
                    @if(!empty($logos['right']))
                    <img src="{{ $logos['right'] }}" style="max-width: 120px; height: auto;">
                    @else
                    <div style="color: #666; font-size: 9pt;"><strong>VANTAGE VENTURES</strong></div>
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
                        <div style="font-size: 7pt;">Powered By</div>
                        <div style="font-weight:500;">ZED CAPITAL</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ===================== MAIN CONTENT ===================== --}}
    
    <h1>FINANCE CLEARANCE</h1>

    {{-- Basic Information --}}
    <div class="section-title">Unit & Client Information</div>
    <table class="info-table">
        <tr>
            <td class="label">DATE</td>
            <td class="value">{{ $date }}</td>
        </tr>
        <tr>
            <td class="label">UNIT NO.</td>
            <td class="value">{{ $unit->unit }}</td>
        </tr>
        <tr>
            <td class="label">PROJECT</td>
            <td class="value">{{ strtoupper($property->project_name) }}</td>
        </tr>
        <tr>
            <td class="label">CLIENT NAME</td>
            <td class="value">{{ strtoupper($client_name) }}</td>
        </tr>
    </table>

    {{-- Payment Summary --}}
    <div class="section-title">Payment Summary</div>
    <table class="payment-summary">
        <tr>
            <td class="label">PURCHASE PRICE</td>
            <td class="amount">AED {{ number_format($purchase_price, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td class="label">4% DLD + ADMIN FEE</td>
            <td class="amount">AED {{ number_format(($unit->dld_fees ?? 0) + ($unit->admin_fee ?? 0), 2, '.', ',') }}</td>
        </tr>
        <tr class="highlight">
            <td class="label"><strong>TOTAL AMOUNT</strong></td>
            <td class="amount">AED {{ number_format($total_amount, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td class="label">TOTAL AMOUNT RECEIVED</td>
            <td class="amount" style="color: #28a745;">AED {{ number_format($total_received, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td class="label">AMOUNT PAID TOWARDS 4% DLD + ADMIN FEE</td>
            <td class="amount">AED {{ number_format($amount_paid_towards_dld_admin, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td class="label">AMOUNT PAID TOWARDS PURCHASE PRICE</td>
            <td class="amount">AED {{ number_format($amount_paid_towards_purchase, 2, '.', ',') }}</td>
        </tr>
        @if($excess_payment > 0)
        <tr>
            <td class="label" style="color: #17a2b8;"><strong>EXCESS BALANCE</strong></td>
            <td class="amount" style="color: #17a2b8;"><strong>AED {{ number_format($excess_payment, 2, '.', ',') }}</strong></td>
        </tr>
        @endif
    </table>

    {{-- Requirements Checklist --}}
    <div class="section-title">Requirements Checklist</div>
    <table class="requirements-table">
        <thead>
            <tr>
                <th>REQUIRED</th>
                <th>STATUS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="requirement-label">1. BUYER / REPRESENTATIVE DOCUMENTS PROVIDED</td>
                <td class="requirement-value {{ strtolower($requirement1) }}">{{ $requirement1 }}</td>
            </tr>
            <tr>
                <td class="requirement-label">2. UTILITY CONNECTIONS COMPLETED</td>
                <td class="requirement-value {{ strtolower($requirement2) }}">{{ $requirement2 }}</td>
            </tr>
            <tr>
                <td class="requirement-label">3. 100% PURCHASE PRICE PAID</td>
                <td class="requirement-value {{ strtolower($requirement3) }}">{{ $requirement3 }}</td>
            </tr>
            <tr>
                <td class="requirement-label">4. LATE PAYMENT CHARGES ON INSTALLMENTS PAID (IF ANY)</td>
                <td class="requirement-value {{ strtolower($requirement4) }}">
                    {{ $requirement4 }}
                    <!-- @if($requirement4 === 'YES' && $requirement4_amount)
                        <br><span style="font-size: 7.5pt;">(AED {{ number_format($requirement4_amount, 2, '.', ',') }})</span>
                    @endif -->
                </td>
            </tr>
            <tr>
                <td class="requirement-label">5. BOUNCED CHEQUE CHARGES PAID (IF ANY)</td>
                <td class="requirement-value {{ strtolower($requirement5) }}">
                    {{ $requirement5 }}
                    <!-- @if($requirement5 === 'YES' && $requirement5_amount)
                        <br><span style="font-size: 7.5pt;">(AED {{ number_format($requirement5_amount, 2, '.', ',') }})</span>
                    @endif -->
                </td>
            </tr>
            <tr>
                <td class="requirement-label">6. TITLE DEED FEE PAID</td>
                <td class="requirement-value {{ strtolower($requirement6) }}">{{ $requirement6 }}</td>
            </tr>
            <tr>
                <td class="requirement-label">7. PDC FOR POST HANDOVER PAYMENT PLAN (IF APPLICABLE)</td>
                <td class="requirement-value {{ strtolower($requirement7) }}">{{ $requirement7 }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Remarks --}}
    @if(!empty($remarks))
    <div class="section-title">Remarks</div>
    <div class="remarks-box">
        <div class="remarks-title">REMARKS</div>
        <div class="remarks-text">{{ strtoupper($remarks) }}</div>
    </div>
    @endif

    {{-- Footer Note --}}
    <div style="margin-top: 15px; padding: 8px; background-color: #f9f9f9; border-left: 3px solid #111; font-size: 7.5pt;">
        <p style="margin: 0; line-height: 1.3;">
            <strong>Note:</strong> This finance clearance document is generated based on the current payment status and requirements verification as of {{ $date }}. 
            All information is subject to final verification and reconciliation.
        </p>
    </div>

    {{-- Generated timestamp --}}
    <div style="margin-top: 10px; text-align: center; font-size: 7pt; color: #999;">
        Generated on {{ now()->format('F d, Y \a\t h:i A') }}
    </div>

</body>
</html>
