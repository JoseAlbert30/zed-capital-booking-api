<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Statement of Account - Unit {{ $unit->unit }}</title>
    <style>
        @page {
            margin: 90px 40px 70px 40px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #111;
            line-height: 1.2;
        }

        .header {
            position: fixed;
            top: -70px;
            left: 0;
            right: 0;
            height: 65px;
        }

        .footer {
            position: fixed;
            bottom: -55px;
            left: 0;
            right: 0;
            height: 45px;
            color: #fff;
        }

        .footer-bar {
            background: #111;
            height: 45px;
            padding: 8px 12px;
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
            font-size: 14pt;
            font-weight: 600;
            margin: 0 0 5px 0;
            color: #111;
            text-transform: uppercase;
        }

        .document-subtitle {
            font-size: 10pt;
            font-weight: 500;
            margin-bottom: 3px;
        }
        
        .info-section {
            margin-bottom: 8px;
        }
        
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
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
            font-size: 11pt;
            font-weight: 600;
            margin: 12px 0 6px 0;
            padding: 5px 0;
            border-bottom: 2px solid #111;
            text-transform: uppercase;
        }
        
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }
        
        .payment-table th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            font-weight: 600;
            font-size: 8pt;
            text-transform: uppercase;
        }
        
        .payment-table td {
            border: 1px solid #ddd;
            padding: 5px;
            font-size: 9pt;
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
            padding: 8px;
            margin: 8px 0;
        }
        
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        
        .summary-label {
            display: table-cell;
            width: 70%;
            font-weight: 600;
            font-size: 10pt;
        }
        
        .summary-amount {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-weight: 600;
            font-size: 10pt;
        }
        
        .notes {
            margin-top: 10px;
            font-size: 8pt;
        }
        
        .notes-title {
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        ul {
            margin: 4px 0;
            padding-left: 20px;
        }
        
        li {
            margin-bottom: 3px;
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
    <p style="font-size: 9pt; color: #666; margin-bottom: 6px;">Generated: {{ date('d M Y') }}</p>

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
        
        <div class="info-row">
            <div class="info-label">Email{{ count($owners) > 1 ? 's' : '' }}:</div>
            <div class="info-value">
                @php
                    $emails = $owners->filter(fn($o) => !empty($o->email))->pluck('email')->toArray();
                    echo implode(', ', $emails);
                @endphp
            </div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Mobile{{ count($owners) > 1 ? 's' : '' }}:</div>
            <div class="info-value">
                @php
                    $mobiles = $owners->filter(fn($o) => !empty($o->mobile_number))->pluck('mobile_number')->toArray();
                    echo implode(', ', $mobiles);
                @endphp
            </div>
        </div>
        
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
            
            @if($unit->has_pho == 1 || $unit->has_pho === true)
                @if($unit->upon_completion_amount !== null)
                <tr>
                    <td>Upon Completion Amount To Pay</td>
                    <td class="text-right amount">{{ number_format($unit->upon_completion_amount, 2) }}</td>
                </tr>
                @endif
                
                @if($unit->pdc_in_hand !== null && $unit->pdc_in_hand > 0)
                <tr>
                    <td>Total Outstanding Received in PDC (IN HAND)</td>
                    <td class="text-right amount" style="color: #17a2b8;">{{ number_format($unit->pdc_in_hand, 2) }}</td>
                </tr>
                @if($unit->pdc_count !== null)
                <tr>
                    <td style="padding-left: 20px; font-size: 8pt; color: #666;">Number of PDCs</td>
                    <td class="text-right" style="font-size: 8pt; color: #666;">{{ $unit->pdc_count }}</td>
                </tr>
                @endif
                @endif
                
                @if($unit->due_after_completion !== null)
                <tr class="outstanding-row">
                    <td><strong>Due After Completion</strong></td>
                    <td class="text-right amount" style="color: {{ abs($unit->due_after_completion) > 0 ? '#dc3545' : '#28a745' }};">
                        {{ number_format(abs($unit->due_after_completion), 2) }}
                    </td>
                </tr>
                @endif
            @else
                @if($unit->outstanding_amount !== null)
                <tr class="outstanding-row">
                    <td><strong>Outstanding Balance</strong></td>
                    <td class="text-right amount" style="color: {{ $unit->outstanding_amount > 0 ? '#dc3545' : '#28a745' }};">
                        {{ number_format($unit->outstanding_amount, 2) }}
                    </td>
                </tr>
                @endif
            @endif
        </tbody>
    </table>

    @if($unit->has_pho == 1 || $unit->has_pho === true)
    <div class="summary-box">
        <div class="summary-row">
            <div class="summary-label">Payment Status:</div>
            <div class="summary-amount" style="color: {{ abs($unit->due_after_completion) > 0 ? '#dc3545' : '#28a745' }};">
                @if($unit->due_after_completion > 0)
                    BALANCE DUE AFTER COMPLETION
                @elseif($unit->due_after_completion < 0 && $unit->pdc_in_hand > 0)
                    PDC IN HAND
                @elseif($unit->due_after_completion < 0)
                    OVERPAID
                @else
                    FULLY PAID
                @endif
            </div>
        </div>
        
        @if($unit->due_after_completion > 0)
        <div class="summary-row">
            <div class="summary-label">Amount Due After Completion:</div>
            <div class="summary-amount" style="color: #dc3545;">
                AED {{ number_format(abs($unit->due_after_completion), 2) }}
            </div>
        </div>
        @elseif($unit->due_after_completion < 0 && $unit->pdc_in_hand > 0)
        <div class="summary-row">
            <div class="summary-label">PDC Amount:</div>
            <div class="summary-amount" style="color: #17a2b8;">
                AED {{ number_format(abs($unit->due_after_completion), 2) }}
            </div>
        </div>
        @elseif($unit->due_after_completion < 0)
        <div class="summary-row">
            <div class="summary-label">Overpayment:</div>
            <div class="summary-amount" style="color: #28a745;">
                AED {{ number_format(abs($unit->due_after_completion), 2) }}
            </div>
        </div>
        @endif
    </div>
    @else
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
        <ul style="margin-left: 20px; margin-top: 3px;">
            <li>All amounts are in UAE Dirhams (AED)</li>
            <li>This is an automatically generated statement</li>
            <li>Please retain this document for your records</li>
            @if($unit->has_pho == 1 || $unit->has_pho === true)
                @if($unit->pdc_in_hand !== null && $unit->pdc_in_hand > 0)
                <li><strong>PDCs (Post-Dated Cheques) are shown for transparency but do not reduce the outstanding balance</strong></li>
                @endif
            @endif
            @if($unit->outstanding_amount > 0)
            <li><strong>Outstanding balance must be cleared before handover</strong></li>
            @endif
        </ul>
    </div>

    <div style="margin-top: 25px; margin-bottom: 50px; padding: 8px 12px; background-color: #f9f9f9; border-left: 4px solid #111; font-size: 8pt;">
        <p style="margin: 0; line-height: 1.4;">
            <strong>Disclaimer:</strong> This Statement of Account does not reflect applicable late payment fees. If any, the amount will be communicated upon the full settlement and clearance of all outstanding balances and according to the SPA terms and conditions.
        </p>
    </div>

</body>
</html>
