<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Declaration of Adherence and Acknowledgment</title>
    <style>
        /* Dompdf-safe styles (avoid flex/grid) */
        @page {
            margin: 110px 40px 85px 40px;
        }

        /* top right bottom left */

        body {
            font-family: Poppins, Arial, sans-serif;
            font-size: 9pt;
            color: #111;
            line-height: 1.35;
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

        .box-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .box-table td,
        .box-table th {
            border: 1px solid #333;
            padding: 4px 6px;
            vertical-align: top;
            font-size: 7pt;
        }

        .box-table th {
            background: #f3f3f3;
            font-weight: 500;
            text-align: left;
            width: 18%;
        }

        .section-title {
            font-weight: 500;
            margin: 14px 0 6px;
            text-decoration: underline;
        }

        .clause-title {
            font-weight: 500;
            margin-top: 10px;
        }

        .clauses {
            margin-top: 8px;
        }

        .clauses table {
            width: 100%;
            border-collapse: collapse;
        }

        .clauses td {
            vertical-align: top;
            padding: 2px 0;
        }

        .num {
            width: 34px;
            font-weight: 500;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .sign-table td {
            padding: 6px 4px;
            vertical-align: bottom;
        }

        .line {
            border-bottom: 1px solid #111;
            height: 14px;
        }

        .page-break {
            page-break-after: always;
        }

        .annex-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .annex-table th,
        .annex-table td {
            border: 1px solid #cfcfcf;
            padding: 10px;
            height: 26px;
        }

        .annex-table th {
            background: #f6f6f6;
            font-weight: 500;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .spacer-8 {
            height: 8px;
        }

        .spacer-12 {
            height: 12px;
        }

        .spacer-18 {
            height: 18px;
        }

        .ack-title {
            font-size: 12pt;
            font-weight: 500;
            text-decoration: underline;
            margin-top: 10px;
        }

        .ack-box {
            margin-top: 14px;
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

        <div class="title-bar">DECLARATION OF ADHERENCE AND ACKNOWLEDGMENT</div>
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

    {{-- ===================== PAGE 1 ===================== --}}
    <table class="box-table">
        <tr>
            <th style="width: 20%;">Date</th>
            <td style="width: 80%;">{{ $date ?? '' }}</td>
        </tr>

        <tr>
            <th rowspan="4">Seller</th>
            <td><strong>Name</strong> &nbsp;VANTAGE VENTURES REAL ESTATE DEVELOPMENT L.L.C</td>
        </tr>
        <tr>
            <td><strong>Address</strong> &nbsp;Office No.12F-A-05, Empire Heights Tower A, Business Bay, Dubai, United Arab Emirates.</td>
        </tr>
        <tr>
            <td><strong>Phone/Mobile Number</strong> &nbsp; +971 4 422 7153</td>
        </tr>
        <tr>
            <td><strong>Email Address</strong> &nbsp; inquire@vantageventures.ae</td>
        </tr>

        <tr>
            <th rowspan="4">Purchaser{{ count($coOwners ?? []) > 0 ? 's' : '' }}</th>
            <td><strong>Name</strong> &nbsp; {{ $purchaser['name'] ?? '' }}@if(isset($coOwners) && count($coOwners) > 0)@foreach($coOwners as $coOwner) & {{ $coOwner->full_name ?? $coOwner->name ?? '' }}@endforeach @endif</td>
        </tr>
        <tr>
            <td><strong>Address</strong> &nbsp; {{ $purchaser['address'] ?? '' }}</td>
        </tr>        <tr>
            <td><strong>Phone/Mobile Number</strong> &nbsp; {{ $purchaser['phone'] ?? '' }}@if(isset($coOwners) && count($coOwners) > 0)@foreach($coOwners as $coOwner)@if(!empty($coOwner->mobile_number)) & {{ $coOwner->mobile_number }}@endif @endforeach @endif</td>
        </tr>
        <tr>
            <td><strong>Email Address</strong> &nbsp; {{ $purchaser['email'] ?? '' }}@if(isset($coOwners) && count($coOwners) > 0)@foreach($coOwners as $coOwner) & {{ $coOwner->email ?? '' }}@endforeach @endif</td>
        </tr>

        <tr>
            <th rowspan="3">Property</th>
            <td><strong>Master Community</strong> &nbsp; {{ $property['master_community'] ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Building</strong> &nbsp; Viera Residences</td>
        </tr>
        <tr>
            <td><strong>Unit Number</strong> &nbsp; {{ $property['unit_number'] ?? '' }}</td>
        </tr>
    </table>

    <div class="section-title">1. Definitions and Interpretation</div>

    <div class="section-title" style="text-decoration:none;">BACKGROUND:</div>
    <div>
        THIS DECLARATION OF ADHERENCE AND ACKNOWLEDGEMENT is made BETWEEN the Seller and the Purchaser as described in and on the date set out in the Particulars (this Declaration).
    </div>

    <div class="spacer-8"></div>
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:28px;">(A)</td>
            <td>The Parties have entered into a sale and purchase agreement (the SPA) under which the Seller agreed to sell and the Purchaser agreed to purchase the Property subject to the terms of the SPA and the Governance Documents.</td>
        </tr>
        <tr>
            <td>(B)</td>
            <td>In consideration of the Purchaser satisfying its obligations under the SPA, the Seller has handed over the Property to the Purchaser.</td>
        </tr>
        <tr>
            <td>(C)</td>
            <td>The Purchaser acknowledges handover of the Property upon the conditions set out in the SPA and this Declaration.</td>
        </tr>
        <tr>
            <td>(D)</td>
            <td>The Purchaser agrees to be bound by the terms of the Governance Documents as further set out in this Declaration.</td>
        </tr>
    </table>

    <div class="spacer-12"></div>
    <div style="font-weight:500;">NOW THE PURCHASER AGREES AND DECLARES AS FOLLOWS:</div>

    <div class="clauses">
        <table>
            <tr>
                <td class="num">1.1</td>
                <td>In this Declaration, except where the context otherwise requires, the capitalized words shall have the meanings defined in the SPA.</td>
            </tr>
        </table>

        <div class="clause-title">Acknowledgment of Property</div>

        <table>
            <tr>
                <td class="num">1.2</td>
                <td>
                    The Purchaser has inspected the Property (or waived its right to inspect the Property) and unconditionally and irrevocably accepts possession of the Property in good condition ready for occupancy and constructed in accordance with the SPA and free from any and all defects and deficiencies (except as listed in the Annexure attached to this Declaration).
                </td>
            </tr>
            <tr>
                <td class="num">1.3</td>
                <td>
                    The Purchaser releases and discharges the Seller and its nominees, representatives and subsidiaries (including past, present and future successors, officers, directors, agents and employees) from all claims, damages (including general, special, punitive, liquidated and compensatory damages) and causes of action of every kind, nature and character, known or unknown, fixed or contingent, which the Purchaser may now have or the Purchaser may ever had arising from or in any way connected to the Property.
                </td>
            </tr>
            <tr>
                <td class="num">1.4</td>
                <td>
                    The foregoing acceptance, release and discharge is without prejudice to the provisions contained in the SPA regarding rectification of any defects in the Property by the Seller following Handover.
                </td>
            </tr>
            <tr>
                <td class="num">1.5</td>
                <td>
                    The Purchaser acknowledges that it is the sole responsibility of the Purchaser to subscribe (register) to and pay all relevant charges in relation to all utilities provided in the Property.
                </td>
            </tr>
            <tr>
                <td class="num">1.6</td>
                <td>
                    The Purchaser acknowledges and agrees that all utilities provisions within the Property have been provided and that it is the sole responsibility of the Purchaser that utilities within the Property are available to minimize damage due to the prevailing weather conditions in the UAE.
                </td>
            </tr>
            <tr>
                <td class="num">1.7</td>
                <td>
                    The Purchaser acknowledges that leaving the Property not air-conditioned, especially during summer months, may result in damage to the woodwork/joinery, flooring, false ceilings, wall paint and appliances. The Purchaser releases and discharges the Seller and any of its nominees or representatives or subsidiaries from all claims, damages and causes of action arising from this effect.
                </td>
            </tr>
        </table>
    </div>


    {{-- ===================== PAGE 2 ===================== --}}
    <div class="clause-title">Purchaser's Covenants and Warranties</div>

    <div class="clauses">
        <table>
            <tr>
                <td class="num">1.8</td>
                <td>The Purchaser covenants and warrants that the Purchaser shall observe, perform and comply with all the terms, conditions and obligations contained in the Governance Documents and the SPA at all times.</td>
            </tr>
        </table>

        <div class="clause-title">Authority to Amend</div>
        <table>
            <tr>
                <td class="num">1.9</td>
                <td>The Purchaser agrees that the Governance Documents may be varied as per their terms or as required to comply with any applicable law or as may be required by the Land Department or RERA from time to time.</td>
            </tr>
            <tr>
                <td class="num">1.10</td>
                <td>Once notice of any variation of the Governance Documents is served on the Purchaser such variation shall be deemed to be valid, binding and enforceable upon the Purchaser and shall form an integral part of this Declaration.</td>
            </tr>
        </table>

        <div class="clause-title">Authority to Register</div>
        <table>
            <tr>
                <td class="num">1.11</td>
                <td>The Purchaser agrees that the Governance Documents may be Registered by the Land Department against the title to the Property or the Community or part thereof as a restriction and/or positive covenant.</td>
            </tr>
        </table>

        <div class="clause-title">Purchaser’s Indemnity</div>
        <table>
            <tr>
                <td class="num">1.12</td>
                <td>The Purchaser indemnifies the Seller against all actions, costs, claims, damages, demands, expenses, liabilities and losses suffered by the Seller in connection with the Purchaser's breach of its obligations under this Declaration, the SPA and/or the Governance Documents.</td>
            </tr>
        </table>

        <div class="clause-title">Acknowledgment of Understanding</div>
        <table>
            <tr>
                <td class="num">1.13</td>
                <td>The Purchaser agrees that it understands the Purchaser's rights and obligations under this Declaration and the Governance Documents.</td>
            </tr>
        </table>

        <div class="clause-title">Authority to Execute Documents</div>
        <table>
            <tr>
                <td class="num">1.14</td>
                <td>
                    The Purchaser warrants and represents that:
                    <br><br>
                    (a) in the case of the Purchaser being (or including) an individual, the Purchaser has full authority, power and capacity to execute, deliver and perform this Declaration; and
                    <br><br>
                    (b) in the case of the Purchaser being (or including) an entity other than an individual, the execution, delivery and performance of this Declaration by the Purchaser has been duly authorized in accordance with the relevant corporate or other procedures of the Purchaser, no further action on the part of the Purchaser is necessary to authorize such execution, delivery and performance and the person signing this Declaration on behalf of the Purchaser is fully authorized to enter into this Declaration on behalf of the Purchaser.
                </td>
            </tr>
        </table>

        <div class="clause-title">Further Assurances</div>
        <table>
            <tr>
                <td class="num">1.15</td>
                <td>The Purchaser agrees to immediately sign any documents required by the Land Department and/or RERA as may be necessary to enable Registration of the Governance Documents.</td>
            </tr>
        </table>

        <div class="clause-title">Confidentiality</div>
        <table>
            <tr>
                <td class="num">1.16</td>
                <td>The Parties must keep the terms of this Declaration and any information provided by the Seller (and/or its Affiliates) strictly confidential.</td>
            </tr>
        </table>

        <div class="clause-title">Governing Law and Jurisdiction</div>
        <table>
            <tr>
                <td class="num">1.17</td>
                <td>This Declaration and the rights of the Parties set out in it shall be governed by and construed in accordance with the laws of the Emirate of Dubai and the applicable Federal Laws of the UAE. The Parties agree to submit to the exclusive jurisdiction of the Courts of the Emirate of Dubai.</td>
            </tr>
        </table>
    </div>

    <div class="spacer-18"></div>
    <div style="font-size: 9pt; margin-bottom: 12px;">
       I hereby confirm that I have read and understood the declaration of adherence and acknowledgement letter and received the keys for the above-mentioned unit.
    </div>

    <table class="sign-table">
        @if(isset($signaturesData['part1']) && is_array($signaturesData['part1']))
            @foreach($signaturesData['part1'] as $signature)
            <tr>
                <td style="width:18%; font-weight:500;">
                    @if($signature['type'] === 'primary')
                        Purchaser
                    @elseif($signature['type'] === 'secondary')
                        Joint Purchaser
                    @else
                        Purchaser POA
                    @endif
                </td>
                <td style="width:42%;">
                    {{ $signature['name'] ?? '' }}
                </td>
                <td style="width:15%; font-weight:500;" class="center">Signature</td>
                <td style="width:25%; text-align:center;">
                    @if(isset($signature['image']))
                    <img src="{{ $signature['image'] }}" style="max-height: 40px; max-width: 150px;">
                    @endif
                </td>
            </tr>
            @endforeach
        @elseif(isset($isBlankTemplate) && $isBlankTemplate)
            <tr>
                <td style="width:18%; font-weight:500;">Purchaser</td>
                <td style="width:42%;"></td>
                <td style="width:15%; font-weight:500;" class="center">Signature</td>
                <td style="width:25%; text-align:center;"></td>
            </tr>
        @endif
    </table>

    @if(isset($defects) && count($defects) > 0)
    <div class="page-break"></div>

    {{-- ===================== PAGE 3 (ANNEXURE) ===================== --}}
    <div class="center" style="font-weight:500; margin-top: 8px;">
        Annexure 1 – List of Agreed Defects for<br>Remediation
    </div>

    @foreach($defects as $index => $defect)
    <div style="margin-bottom: 20px; page-break-inside: avoid; @if($defect->is_remediated) background-color: #f0fdf4; border: 2px solid #86efac; padding: 10px; border-radius: 4px; @endif">
        <div style="display: table; width: 100%; margin-bottom: 8px;">
            <div style="display: table-cell; font-weight: 600; font-size: 11px; color: #333;">
                Defect #{{ $index + 1 }}
            </div>
            @if($defect->is_remediated)
            <div style="display: table-cell; text-align: right; vertical-align: middle;">
                <span style="background-color: #16a34a; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 8px; font-weight: 600;">
                    &#10003; RESOLVED
                </span>
            </div>
            @endif
        </div>
        <table style="width: 100%; border-collapse: collapse; border: none;">
            <tr>
                <td style="width: 150px; vertical-align: top; padding: 0; border: none;">
                    @if(isset($defect->image_base64) && $defect->image_base64)
                    <img src="{{ $defect->image_base64 }}" style="width: 140px; height: auto; max-height: 120px; display: block;">
                    @else
                    <div style="width: 140px; height: 100px; background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #999;">
                        No image
                    </div>
                    @endif
                </td>
                <td style="vertical-align: top; padding-left: 15px; border: none;">
                    <div style="margin-bottom: 10px;">
                        <div style="font-weight: 600; font-size: 9px; color: #666; margin-bottom: 3px;">DESCRIPTION:</div>
                        <div style="font-size: 10px; line-height: 1.4; color: #333;">
                            {{ $defect->description ?? 'No description provided' }}
                        </div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <div style="font-weight: 600; font-size: 9px; color: #666; margin-bottom: 3px;">LOCATION:</div>
                        <div style="font-size: 10px; line-height: 1.4; color: #333;">
                            {{ $defect->location ?? 'Not specified' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 9px; color: #666; margin-bottom: 3px;">AGREED REMEDIATION ACTION:</div>
                        <div style="font-size: 10px; line-height: 1.4; color: #333;">
                            {{ $defect->agreed_remediation_action ?? 'To be remediated' }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        <div style="border-bottom: 1px solid #e0e0e0; margin-top: 15px;"></div>
    </div>
    @endforeach

    <table class="sign-table" style="margin-top: 26px;">
        @if(isset($signaturesData['part2']) && is_array($signaturesData['part2']))
            @foreach($signaturesData['part2'] as $signature)
            <tr>
                <td style="width:18%; font-weight:500;">
                    @if($signature['type'] === 'primary')
                        Purchaser
                    @elseif($signature['type'] === 'secondary')
                        Joint Purchaser
                    @else
                        Purchaser POA
                    @endif
                </td>
                <td style="width:42%;">
                    {{ $signature['name'] ?? '' }}
                </td>
                <td style="width:15%; font-weight:500;" class="center">Signature</td>
                <td style="width:25%; text-align:center;">
                    @if(isset($signature['image']))
                    <img src="{{ $signature['image'] }}" style="max-height: 40px; max-width: 150px;">
                    @endif
                </td>
            </tr>
            @endforeach
        @endif
    </table>
    @elseif(isset($isBlankTemplate) && $isBlankTemplate)
    <div class="page-break"></div>

    {{-- ===================== PAGE 3 (BLANK ANNEXURE TEMPLATE) ===================== --}}
    <div class="center" style="font-weight:500; margin-top: 8px;">
        Annexure 1 – List of Agreed Defects for<br>Remediation
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; border: 1px solid #333;">
        <thead>
            <tr style="background-color: #f3f3f3;">
                <th style="border: 1px solid #333; padding: 8px; text-align: left; font-weight: 600; font-size: 9px; width: 5%;">#</th>
                <th style="border: 1px solid #333; padding: 8px; text-align: left; font-weight: 600; font-size: 9px; width: 35%;">DESCRIPTION</th>
                <th style="border: 1px solid #333; padding: 8px; text-align: left; font-weight: 600; font-size: 9px; width: 20%;">LOCATION</th>
                <th style="border: 1px solid #333; padding: 8px; text-align: left; font-weight: 600; font-size: 9px; width: 40%;">AGREED REMEDIATION ACTION</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 1; $i <= 5; $i++)
            <tr>
                <td style="border: 1px solid #333; padding: 20px 8px; font-size: 9px; color: #999; text-align: center;">{{ $i }}</td>
                <td style="border: 1px solid #333; padding: 20px 8px;">&nbsp;</td>
                <td style="border: 1px solid #333; padding: 20px 8px;">&nbsp;</td>
                <td style="border: 1px solid #333; padding: 20px 8px;">&nbsp;</td>
            </tr>
            @endfor
        </tbody>
    </table>

    <table class="sign-table" style="margin-top: 26px;">
        <tr>
            <td style="width:18%; font-weight:500;">Purchaser</td>
            <td style="width:42%;"></td>
            <td style="width:15%; font-weight:500;" class="center">Signature</td>
            <td style="width:25%; text-align:center;"></td>
        </tr>
    </table>
    @endif

</body>

</html>