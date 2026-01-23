<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Statement of Account - Unit {{ $unit->unit }}</title>
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
            text-transform: uppercase;
        }

        .document-subtitle {
            font-size: 11pt;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .info-label {
            display: table-cell;
            width: 35%;
            font-weight: 600;
            padding-right: 10px;
        }
        
        .info-value {
            display: table-cell;
            width: 65%;
        }
        
        .section-title {
            font-size: 12pt;
            font-weight: 600;
            margin: 20px 0 10px 0;
            padding: 8px 0;
            border-bottom: 2px solid #111;
            text-transform: uppercase;
        }
        
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .payment-table th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            font-size: 9pt;
            text-transform: uppercase;
        }
        
        .payment-table td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 10pt;
        }
        
        .payment-table tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .amount {
            font-weight: 600;
        }
        
        .total-row {
            background-color: #e8e8e8 !important;
            font-weight: 600;
        }
        
        .outstanding-row {
            background-color: #ffe6e6 !important;
            font-weight: 600;
            font-size: 11pt;
        }
        
        .summary-box {
            background-color: #f9f9f9;
            border: 2px solid #333;
            padding: 15px;
            margin: 20px 0;
        }
        
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .summary-label {
            display: table-cell;
            width: 70%;
            font-weight: 600;
            font-size: 11pt;
        }
        
        .summary-amount {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-weight: 600;
            font-size: 11pt;
        }
        
        .notes {
            margin-top: 30px;
            font-size: 9pt;
        }
        
        .notes-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        ul {
            margin: 8px 0;
            padding-left: 25px;
        }
        
        li {
            margin-bottom: 6px;
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
                    <div class="muted small"><strong>{{ strtoupper($property->project_name ?? 'VIERA RESIDENCES') }}</strong></div>
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
    
    <h1>STATEMENT OF ACCOUNT</h1>
    <p class="document-subtitle">{{ $property->project_name }}</p>
    <p style="font-size: 9pt; color: #666; margin-bottom: 20px;">Generated: {{ date('d M Y, H:i') }}</p>

    <div class="info-section">
        <div class="section-title">Property & Client Information</div>
        
        <div class="info-row">
            <div class="info-label">Unit Number:</div>
            <div class="info-value">{{ $unit->unit }}</div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Property:</div>
            <div class="info-value">{{ $property->project_name }}, {{ $property->location }}</div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Client Name{{ count($owners) > 1 ? 's' : '' }}:</div>
            <div class="info-value">
                @foreach($owners as $index => $owner)
                    {{ $owner->full_name }}@if($index < count($owners) - 1), @endif
                @endforeach
            </div>
        </div>
        
        @if(count($owners) > 0 && $owners[0]->email)
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div class="info-value">{{ $owners[0]->email }}</div>
        </div>
        @endif
        
        @if(count($owners) > 0 && $owners[0]->mobile_number)
        <div class="info-row">
            <div class="info-label">Mobile:</div>
            <div class="info-value">{{ $owners[0]->mobile_number }}</div>
        </div>
        @endif
        
        <div class="info-row">
            <div class="info-label">Floor:</div>
            <div class="info-value">{{ $unit->floor }}</div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Building:</div>
            <div class="info-value">{{ $unit->building }}</div>
        </div>
        
        @if($unit->square_footage)
        <div class="info-row">
            <div class="info-label">Area:</div>
            <div class="info-value">{{ number_format($unit->square_footage, 2) }} sq ft</div>
        </div>
        @endif
    </div>

    @if($unit->total_unit_price !== null || $unit->dld_fees !== null || $unit->admin_fee !== null)
    <div class="section-title">Payment Breakdown</div>
    
    <table class="payment-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount (AED)</th>
            </tr>
        </thead>
        <tbody>
            @if($unit->total_unit_price !== null)
            <tr>
                <td>Total Unit Price</td>
                <td class="text-right amount">{{ number_format($unit->total_unit_price, 2) }}</td>
            </tr>
            @endif
            
            @if($unit->dld_fees !== null)
            <tr>
                <td>DLD Fees</td>
                <td class="text-right amount">{{ number_format($unit->dld_fees, 2) }}</td>
            </tr>
            @endif
            
            @if($unit->admin_fee !== null)
            <tr>
                <td>Admin Fee</td>
                <td class="text-right amount">{{ number_format($unit->admin_fee, 2) }}</td>
            </tr>
            @endif
            
            @if($unit->amount_to_pay !== null)
            <tr class="total-row">
                <td><strong>Total Amount to Pay</strong></td>
                <td class="text-right amount">{{ number_format($unit->amount_to_pay, 2) }}</td>
            </tr>
            @endif
            
            @if($unit->total_amount_paid !== null)
            <tr>
                <td>Total Amount Paid</td>
                <td class="text-right amount" style="color: #28a745;">{{ number_format($unit->total_amount_paid, 2) }}</td>
            </tr>
            @endif
            
            @if($unit->outstanding_amount !== null)
            <tr class="outstanding-row">
                <td><strong>Outstanding Balance</strong></td>
                <td class="text-right amount" style="color: {{ $unit->outstanding_amount > 0 ? '#dc3545' : '#28a745' }};">
                    {{ number_format($unit->outstanding_amount, 2) }}
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    @if($unit->outstanding_amount !== null)
    <div class="summary-box">
        <div class="summary-row">
            <div class="summary-label">Payment Status:</div>
            <div class="summary-amount" style="color: {{ $unit->outstanding_amount > 0 ? '#dc3545' : '#28a745' }};">
                @if($unit->outstanding_amount > 0)
                    BALANCE DUE
                @elseif($unit->outstanding_amount < 0)
                    OVERPAID
                @else
                    FULLY PAID
                @endif
            </div>
        </div>
        
        @if($unit->outstanding_amount > 0)
        <div class="summary-row">
            <div class="summary-label">Amount Due:</div>
            <div class="summary-amount" style="color: #dc3545;">
                AED {{ number_format($unit->outstanding_amount, 2) }}
            </div>
        </div>
        @elseif($unit->outstanding_amount < 0)
        <div class="summary-row">
            <div class="summary-label">Credit Balance:</div>
            <div class="summary-amount" style="color: #28a745;">
                AED {{ number_format(abs($unit->outstanding_amount), 2) }}
            </div>
        </div>
        @endif
    </div>
    @endif
    @endif

    <div class="notes">
        <div class="notes-title">Important Notes:</div>
        <ul style="margin-left: 20px; margin-top: 5px;">
            <li>All amounts are in UAE Dirhams (AED)</li>
            <li>This is an automatically generated statement</li>
            <li>Please retain this document for your records</li>
            @if($unit->outstanding_amount > 0)
            <li><strong>Outstanding balance must be cleared before handover</strong></li>
            @endif
        </ul>
    </div>

</body>
</html>
