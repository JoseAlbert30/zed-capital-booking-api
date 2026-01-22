<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Handover Checklist</title>

    <style>
        /* Dompdf-safe defaults */
        @page {
            margin: 120px 35px 95px 35px;
            /* room for fixed header/footer */
        }

        * {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .header {
            position: fixed;
            top: -95px;
            left: 0;
            right: 0;
            height: 85px;
        }

        .footer {
            position: fixed;
            bottom: -75px;
            left: 0;
            right: 0;
            height: 65px;
        }

        .footer-bar {
            background: #111;
            padding: 12px 14px;
            color: #fff;
        }

        .footer-bar,
        .footer-bar * {
            color: #fff;
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

        .spacer-8 {
            height: 8px;
        }

        .wrap {
            width: 100%;
        }

        .top-row {
            width: 100%;
            border-collapse: collapse;
        }

        .top-row td {
            vertical-align: top;
        }

        .logo-box {
            height: 55px;
        }

        .logo-text {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .sub-logo-text {
            font-size: 9px;
            letter-spacing: 2px;
        }

        .date-box {
            text-align: right;
        }

        .date-label {
            font-size: 11px;
            font-weight: bold;
        }

        .line {
            display: inline-block;
            border-bottom: 1px solid #000;
            width: 120px;
            height: 14px;
            vertical-align: bottom;
        }

        .line-wide {
            display: inline-block;
            border-bottom: 1px solid #000;
            width: 100%;
            height: 14px;
            vertical-align: bottom;
        }

        .info {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .info td {
            padding: 6px 0;
            vertical-align: middle;
        }

        .label {
            width: 28%;
            font-weight: bold;
        }

        .value {
            width: 72%;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin: 12px 0 8px;
        }

        .hr {
            height: 6px;
            background: #111;
            margin: 10px 0;
        }

        .check-table {
            width: 100%;
            border-collapse: collapse;
        }

        .check-table td {
            padding: 6px 0;
            vertical-align: middle;
        }

        .check-left {
            width: 43%;
        }

        .check-mid {
            width: 7%;
            text-align: center;
        }

        .check-right {
            width: 43%;
        }

        .check-end {
            width: 7%;
            text-align: center;
        }

        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
        }

        .checkbox.checked::after {
            content: "✓";
            display: block;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            line-height: 10px;
        }

        .note {
            font-size: 10px;
            font-style: italic;
            margin-top: 6px;
        }

        .row-4 {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .row-4 td {
            padding: 6px 0;
            vertical-align: middle;
        }

        .row-4 .item {
            width: 22%;
        }

        .row-4 .box {
            width: 3%;
            text-align: center;
        }

        .remarks {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .remarks td {
            padding: 6px 0;
            vertical-align: middle;
        }

        .remarks .r-label {
            width: 28%;
        }

        .remarks .r-line {
            width: 72%;
        }

        .receivables {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .receivables td {
            padding: 8px 0;
            vertical-align: middle;
        }

        .receivables .r1 {
            width: 28%;
        }

        .receivables .r2 {
            width: 22%;
        }

        .receivables .r3 {
            width: 22%;
        }

        .receivables .r4 {
            width: 28%;
        }

        .decl {
            margin-top: 12px;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .sign-table td {
            padding: 8px 0;
            vertical-align: bottom;
        }

        .sign-name {
            width: 30%;
            font-weight: bold;
        }

        .sign-line {
            width: 40%;
            padding-right: 10px;
        }

        .sign-lbl {
            width: 10%;
            text-align: right;
            font-weight: bold;
            padding-right: 10px;
        }

        .sign-line2 {
            width: 20%;
        }

        .small {
            font-size: 10px;
        }

        .muted {
            color: #333;
        }

        .italic {
            font-style: italic;
        }

        .right {
            text-align: right;
        }

        .sig-img {
            max-height: 35px;
            max-width: 150px;
            display: inline-block;
            vertical-align: bottom;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">
        <table class="brand-row">
            <tr>
                <td style="width: 33%;">
                    @if(!empty($logos['left']))
                    <img src="{{ $logos['left'] }}" style="max-width: 80px; height: auto;">
                    @else
                    <div class="muted small"><strong>VIERA RESIDENCES</strong></div>
                    @endif
                </td>
                <td style="width: 34%;"></td>
                <td style="width: 33%;" class="right">
                    @if(!empty($logos['right']))
                    <img src="{{ $logos['right'] }}" style="max-width: 120px; height: auto;">
                    @else
                    <div class="muted small"><strong>VANTAGE VENTURES</strong></div>
                    @endif
                </td>
            </tr>
        </table>
        <div class="spacer-8"></div>
        <div class="title-bar">HANDOVER CHECKLIST</div>
    </div>

    {{-- FOOTER --}}
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

    {{-- BODY --}}
    <div class="wrap">

        {{-- Date --}}
        <table class="info">
            <tr>
                <td class="label">Date:</td>
                <td class="value"><span style="font-weight:bold; text-decoration: underline;">{{ $date }}</span></td>
            </tr>
        </table>

        {{-- Building / Unit / Purchaser --}}
        <table class="info">
            <tr>
                <td class="label">Building Name:</td>
                <td class="value" colspan="3"><span style="font-weight:bold; text-decoration: underline;">{{ $property->project_name ?? 'Viera Residences' }}</span></td>
            </tr>
            <tr>
                <td class="label">Unit No:</td>
                <td class="value" colspan="3"><span style="text-decoration: underline;">{{ $unit->unit ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td class="label" style="width: 18%;">Purchaser:</td>
                <td class="value" style="width: 32%;"><strong>Name 1</strong> &nbsp; <span style="text-decoration: underline;">{{ $purchaser->full_name ?? 'N/A' }}</span></td>
                @if(isset($coOwners) && count($coOwners) > 0 && isset($coOwners[0]))
                <td class="label" style="width: 18%; font-weight: bold;">Name 2:</td>
                <td class="value" style="width: 32%;"><span style="text-decoration: underline;">{{ $coOwners[0]->full_name ?? $coOwners[0]->name ?? '' }}</span></td>
                @else
                <td style="width: 18%;"></td>
                <td style="width: 32%;"></td>
                @endif
            </tr>
            @if(isset($coOwners) && count($coOwners) > 1)
            <tr>
                <td class="label"></td>
                <td class="value"></td>
                <td class="label" style="font-weight: bold;">Name 3:</td>
                <td class="value"><span style="text-decoration: underline;">{{ $coOwners[1]->full_name ?? $coOwners[1]->name ?? '' }}</span></td>
            </tr>
            @endif
            @if(!empty($formData['poa_signature_name']))
            <tr>
                <td class="label">Purchaser POA <span class="small italic">(if applicable)</span></td>
                <td class="value"><span style="text-decoration: underline;">{{ $formData['poa_signature_name'] }}</span></td>
            </tr>
            @endif
        </table>

        {{-- Document Verification --}}
        <div class="section-title">Document Verification</div>

        <table class="check-table">
            <tr>
                <td class="check-left">Original SPA</td>
                <td class="check-mid"><span class="checkbox @if(!empty($formData['original_spa'])) checked @endif"></span></td>
                <td class="check-right">DEWA registration</td>
                <td class="check-end"><span class="checkbox @if(!empty($formData['dewa_registration'])) checked @endif"></span></td>
            </tr>
            <tr>
                <td class="check-left">Power of Attorney</td>
                <td class="check-mid"><span class="checkbox @if(!empty($formData['poa'])) checked @endif"></span></td>
                <td class="check-right">AC registration</td>
                <td class="check-end"><span class="checkbox @if(!empty($formData['ac_registration'])) checked @endif"></span></td>
            </tr>
            <tr>
                <td class="check-left">Bank NOC <span class="small italic">(verified for mortgage)</span></td>
                <td class="check-mid"><span class="checkbox @if(!empty($formData['bank_noc'])) checked @endif"></span></td>
                <td class="check-right">Passport / ID</td>
                <td class="check-end"><span class="checkbox @if(!empty($formData['passport_id_copy'])) checked @endif"></span></td>
            </tr>
            <tr>
                <td colspan="3">Letter of Discharge and Adherence signed</td>
                <td class="check-end"><span class="checkbox @if(!empty($formData['letter_of_discharge'])) checked @endif"></span></td>
            </tr>
        </table>

        {{-- For Companies --}}
        <div style="margin-top: 10px; font-weight:bold;">For Companies :</div>

        <table class="check-table">
            <tr>
                <td colspan="3">Trade License / Certificate of Incorporation*</td>
                <td class="check-end"><span class="checkbox @if(!empty($formData['trade_license'])) checked @endif"></span></td>
            </tr>
            <tr>
                <td colspan="3">Articles &amp; Memorandum of Association*</td>
                <td class="check-end"><span class="checkbox @if(!empty($formData['articles_association'])) checked @endif"></span></td>
            </tr>
            <tr>
                <td colspan="3">Registered shareholders &amp; Directors of the Company - Share Certificate</td>
                <td class="check-end"><span class="checkbox @if(!empty($formData['shareholders_list'])) checked @endif"></span></td>
            </tr>
            <tr>
                <td colspan="3">Notarised Attested Power of Attorney with company stamp signed</td>
                <td class="check-end"><span class="checkbox @if(!empty($formData['poa'])) checked @endif"></span></td>
            </tr>
        </table>

        <div class="note">
            *In case originals are not available, legally attested copies to be presented
        </div>

        <div class="hr"></div>

        {{-- Visits + checklist received --}}
        <table class="row-4">
            <tr>
                <td class="item">Unit visit</td>
                <td class="box"><span class="checkbox @if(!empty($formData['visit_to_unit'])) checked @endif"></span></td>

                <td class="item" style="padding-left:12px;">Parking visit</td>
                <td class="box"><span class="checkbox @if(!empty($formData['visit_to_parking'])) checked @endif"></span></td>

                <td class="item" style="padding-left:12px;">Amenities visit</td>
                <td class="box"><span class="checkbox @if(!empty($formData['amenities_tour'])) checked @endif"></span></td>

                <td class="item" style="padding-left:12px;">Checklist received</td>
                <td class="box"><span class="checkbox @if(!empty($formData['checklist_received'])) checked @endif"></span></td>
            </tr>
        </table>

        <table class="remarks">
            <tr>
                <td class="r-label">Deficiencies list issued/signed</td>
                <td class="r-line"><span style="text-decoration: underline;">{{ $formData['deficiencies'] ?? '' }}</span></td>
            </tr>
            <tr>
                <td class="r-label">Remarks:</td>
                <td class="r-line"><span style="text-decoration: underline;">{{ $formData['remarks'] ?? '' }}</span></td>
            </tr>
            <tr>
                <td class="r-label">DEWA Premise Number:</td>
                <td class="r-line"><span style="text-decoration: underline;">{{ $formData['dewa_premise_no'] ?? '' }}</span></td>
            </tr>
        </table>

        <div class="hr"></div>

        {{-- Receivables --}}
        <div class="section-title">Receivables</div>

        <table class="receivables">
            <tr>
                <td class="r1">No. of Main Door Keys:</td>
                <td class="r2"><span style="text-decoration: underline;">{{ $formData['main_door_keys'] ?? '' }}</span></td>
                <td class="r3" style="padding-left:14px;">Handover Pack:</td>
                <td class="r4"><span style="text-decoration: underline;">{{ $formData['handover_pack'] ?? '' }}</span></td>
            </tr>
            <tr>
                <td class="r1">No. of Access Cards Issued:</td>
                <td class="r2"><span style="text-decoration: underline;">{{ $formData['access_cards'] ?? '' }}</span></td>
                <td class="r3" style="padding-left:14px;">Card No's:</td>
                <td class="r4"><span style="text-decoration: underline;">{{ $formData['card_numbers'] ?? '' }}</span></td>
            </tr>
        </table>

        <div class="hr"></div>

        {{-- Declaration + Signatures --}}
        <div class="decl">
            I/We hereby declare that I/we have received the above mentioned items.
        </div>

        <table class="sign-table">
            <tr>
                <td class="sign-name">Purchaser</td>
                <td class="sign-line">
                    <span style="text-decoration: underline;">{{ $formData['purchaser_signature_name'] ?? $purchaser->full_name ?? '' }}</span>
                </td>
                <td class="sign-lbl">Signature</td>
                <td class="sign-line2">
                    @if(!empty($formData['purchaser_signature_image']))
                    <img src="{{ $formData['purchaser_signature_image'] }}" class="sig-img" alt="Signature">
                    @else
                    <span class="line-wide"></span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="sign-name">Joint Purchaser</td>
                <td class="sign-line">
                    <span style="text-decoration: underline;">{{ $formData['joint_signature_name'] ?? '' }}</span>
                </td>
                <td class="sign-lbl">Signature</td>
                <td class="sign-line2">
                    @if(!empty($formData['joint_signature_image']))
                    <img src="{{ $formData['joint_signature_image'] }}" class="sig-img" alt="Signature">
                    @else
                    <span class="line-wide"></span>
                    @endif
                </td>
            </tr>
            @if(!empty($formData['poa_signature_name']))
            <tr>
                <td class="sign-name">Purchaser POA <span class="small italic">( if applicable)</span></td>
                <td class="sign-line">
                    <span style="text-decoration: underline;">{{ $formData['poa_signature_name'] }}</span>
                </td>
                <td class="sign-lbl">Signature</td>
                <td class="sign-line2">
                    @if(!empty($formData['poa_signature_image']))
                    <img src="{{ $formData['poa_signature_image'] }}" class="sig-img" alt="Signature">
                    @else
                    <span class="line-wide"></span>
                    @endif
                </td>
            </tr>
            @endif
            <tr>
                <td class="sign-name">Handover/Orientation completed by (Name)</td>
                <td class="sign-line">
                    <span style="text-decoration: underline;">{{ $formData['staff_signature_name'] ?? '' }}</span>
                </td>
                <td class="sign-lbl">Signature</td>
                <td class="sign-line2">
                    @if(!empty($formData['staff_signature_image']))
                    <img src="{{ $formData['staff_signature_image'] }}" class="sig-img" alt="Signature">
                    @else
                    <span class="line-wide"></span>
                    @endif
                </td>
            </tr>
        </table>

    </div>
</body>

</html>