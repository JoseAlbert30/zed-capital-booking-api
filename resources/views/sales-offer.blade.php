<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Offer - {{ $offer->unit_no }}</title>
    <style>
        @page {
            margin: 40px;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            color: black;
            line-height: 1.4;
        }
        .header {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        .header-left img {
            height: 40px;
            width: auto;
            max-width: 240px;
        }
        .header-right {
            position: absolute;
            top: 0;
            right: 0;
            text-align: right;
        }
        .header-right h1 {
            color: black;
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 2px;
        }
        .greeting {
            margin-bottom: 20px;
        }
        .property-details {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border: 1px solid #333;
        }
        .property-details th {
            background-color: black;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 10px;
            border: 1px solid #333;
        }
        .property-details td {
            padding: 10px;
            border: 1px solid #333;
        }
        .notes {
            margin: 20px 0;
        }
        .notes ul {
            list-style: disc;
            padding-left: 20px;
        }
        .notes li {
            margin-bottom: 5px;
        }
        .schedule-title {
            font-size: 14px;
            font-weight: bold;
            margin: 30px 0 15px 0;
            color: black;
        }
        .payment-schedule {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border: 1px solid #333;
        }
        .payment-schedule th {
            background-color: black;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 10px;
            border: 1px solid #333;
        }
        .payment-schedule td {
            padding: 8px 10px;
            border: 1px solid #333;
        }
        .payment-schedule tr:last-child td {
            font-weight: bold;
            background-color: white;
        }
        .total-row {
            background-color: black !important;
            color: black !important;
            font-weight: bold;
        }        .total-row td:first-child {
            border: none !important;
        }
        .total-row td:nth-child(2) {
            border: none !important;
        }        .bank-details {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        .bank-section {
            display: table-cell;
            width: 50%;
            padding-right: 20px;
            vertical-align: top;
        }
        .bank-section:last-child {
            padding-right: 0;
            padding-left: 20px;
        }
        .bank-section h3 {
            color: black;
            font-size: 12px;
            margin-bottom: 10px;
            border-bottom: 2px solid black;
            padding-bottom: 5px;
        }
        .bank-info {
            padding-left: 15px;
        }
        .bank-info p {
            margin: 5px 0;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        .footer {
            width: 100%;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            border-collapse: collapse;
        }
        .footer td {
            vertical-align: top;
            padding: 5px;
        }
        .footer-left {
            text-align: left;
            width: 50%;
        }
        .footer-right {
            text-align: right;
            width: 50%;
        }
        .footer-right p {
            margin: 0 0 5px 0;
            font-size: 9px;
            color: #999;
        }
        .footer-right img {
            height: 60px;
            width: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            @if(!empty($logo))
            <img src="{{ $logo }}" alt="Gate Eleven Logo">
            @endif
        </div>
        <div class="header-right">
            <h1>SALES OFFER</h1>
        </div>
    </div>

    <div class="greeting">
        <p>Dear Valued Client,</p>
        <p>Thank you for your interest in our Project. Please find the property details below.</p>
    </div>

    <table class="property-details">
        <thead>
            <tr>
                <th>Project</th>
                <th>Unit Number</th>
                <th>Bedrooms</th>
                <th>Total Area (Sqft)</th>
                <th>Selling Price (AED)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $offer->project_name }}</td>
                <td>{{ $offer->unit_no }}</td>
                <td>{{ $offer->bedrooms }}</td>
                <td>{{ number_format($offer->sqft, 2) }}</td>
                <td>AED {{ number_format($price, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="notes">
        <ul>
            <li>Applicable fees to Dubai Land Department are: 4% of the property net price + AED 5,250 Admin fees (Including VAT)</li>
            <li>Prices and availability are subject to change without notice</li>
            <li>The Estimated completion Date is mere estimate and is subject to change from time to time.</li>
            <li>This offer is non-transferable and confidential.</li>
            <li>This offer letter does not guarantee availability of these units or any units in Gate Eleven.</li>
        </ul>
    </div>

    <div class="schedule-title">SCHEDULE OF PAYMENTS</div>

    <table class="payment-schedule">
        <thead>
            <tr>
                <th style="width: 18%;">Details</th>
                <th style="width: 30%;">Milestone</th>
                <th style="width: 12%;">(%)</th>
                <th style="width: 20%;">Beneficiary</th>
                <th style="width: 20%;">Total Amount (AED)</th>
            </tr>
        </thead>
        <tbody>
            @if($paymentPlan === '50/50 Payment Plan')
                <!-- 50/50 Payment Plan -->
                <tr>
                    <td>Reservation Deposit</td>
                    <td rowspan="3">On Booking</td>
                    <td>20%</td>
                    <td>GATE ELEVEN</td>
                    <td>AED {{ number_format($price * 0.20, 2) }}</td>
                </tr>
                <tr>
                    <td>DLD Fees</td>
                    <td>4%</td>
                    <td>Amwaj AlKhaleej</td>
                    <td>AED {{ number_format($dldFee, 2) }}</td>
                </tr>
                <tr>
                    <td>Admin Fees</td>
                    <td>-</td>
                    <td>Amwaj AlKhaleej</td>
                    <td>AED {{ number_format($offer->admin_fee, 2) }}</td>
                </tr>
                <tr>
                    <td>1</td>
                    <td>After 3 months of booking</td>
                    <td>5%</td>
                    <td>GATE ELEVEN</td>
                    <td>AED {{ number_format($price * 0.05, 2) }}</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>After 6 months of booking</td>
                    <td>5%</td>
                    <td>GATE ELEVEN</td>
                    <td>AED {{ number_format($price * 0.05, 2) }}</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>After 9 months of booking</td>
                    <td>5%</td>
                    <td>GATE ELEVEN</td>
                    <td>AED {{ number_format($price * 0.05, 2) }}</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>After 12 months of booking</td>
                    <td>5%</td>
                    <td>GATE ELEVEN</td>
                    <td>AED {{ number_format($price * 0.05, 2) }}</td>
                </tr>
                <tr>
                    <td>5</td>
                    <td>After 15 months of booking</td>
                    <td>5%</td>
                    <td>GATE ELEVEN</td>
                    <td>AED {{ number_format($price * 0.05, 2) }}</td>
                </tr>
                <tr>
                    <td>6</td>
                    <td>After 18 months of booking</td>
                    <td>5%</td>
                    <td>GATE ELEVEN</td>
                    <td>AED {{ number_format($price * 0.05, 2) }}</td>
                </tr>
                <tr>
                    <td>7</td>
                    <td>Upon Completion</td>
                    <td>50%</td>
                    <td>GATE ELEVEN</td>
                    <td>AED {{ number_format($price * 0.50, 2) }}</td>
                </tr>
            @else
                <!-- 30/70 Payment Plan -->
                <tr>
                    <td>Reservation Deposit</td>
                    <td rowspan="3">On Booking</td>
                    <td>20%</td>
                    <td>GATE ELEVEN</td>
                    <td>AED {{ number_format($price * 0.20, 2) }}</td>
                </tr>
                <tr>
                    <td>DLD Fees</td>
                    <td>4%</td>
                    <td>Amwaj AlKhaleej</td>
                    <td>AED {{ number_format($dldFee, 2) }}</td>
                </tr>
                <tr>
                    <td>Admin Fees</td>
                    <td>-</td>
                    <td>Amwaj AlKhaleej</td>
                    <td>AED {{ number_format($offer->admin_fee, 2) }}</td>
                </tr>
                <tr>
                    <td>1</td>
                    <td>After 3 months of booking</td>
                    <td>5%</td>
                    <td>GATE ELEVEN</td>
                    <td>AED {{ number_format($price * 0.05, 2) }}</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>After 6 months of booking</td>
                    <td>5%</td>
                    <td>GATE ELEVEN</td>
                    <td>AED {{ number_format($price * 0.05, 2) }}</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Upon Completion</td>
                    <td>70%</td>
                    <td>GATE ELEVEN</td>
                    <td>AED {{ number_format($price * 0.70, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="2"></td>
                <td colspan="2"><strong>Total Price</strong></td>
                <td><strong>AED {{ number_format($totalPrice, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="bank-details">
        <div class="bank-section">
            <h3>Corporate Account Details</h3>
            <div class="bank-info">
                <p><strong>Bank Name:</strong> RAK BANK</p>
                <p><strong>Name:</strong> Amwaj AlKhaleej Real Estate Development LLC</p>
                <p><strong>Current Account No:</strong> 0023491510001</p>
                <p><strong>IBAN:</strong> AE520400000023491510001</p>
                <p><strong>SWIFT:</strong> NRAKAEAK</p>
            </div>
        </div>

        <div class="bank-section">
            <h3>Escrow Account Details</h3>
            <div class="bank-info">
                <p><strong>Bank Name:</strong> Rak Bank</p>
                <p><strong>Branch Name:</strong> Al Nakheel, RAK</p>
                <p><strong>Name:</strong> GATE ELEVEN RESIDENCES</p>
                <p><strong>Account No:</strong> 0023491510003</p>
                <p><strong>IBAN:</strong> AE950400000023491510003</p>
                <p><strong>SWIFT:</strong> NRAKAEAK</p>
                <p><strong>Currency:</strong> AED</p>
            </div>
        </div>
    </div>

    <table class="footer">
        <tr>
            <td class="footer-left">
                <p><strong>Generated by:</strong> Akshay Bhatia</p>
                <p><strong>Date:</strong> {{ date('F d, Y') }}</p>
            </td>
            <td class="footer-right">
                <p>Powered by</p>
                @if(!empty($zedLogo))
                <img src="{{ $zedLogo }}" alt="Zed Capital">
                @endif
            </td>
        </tr>
    </table>
</body>
</html>
