<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .header {
            border-bottom: 3px solid #1a1a1a;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .header p {
            font-size: 11px;
            color: #666;
            margin: 3px 0;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            border-bottom: 1px solid #999;
            padding-bottom: 8px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .detail-grid.full {
            grid-template-columns: 1fr;
        }
        
        .detail-item {
            border: 1px solid #e0e0e0;
            padding: 12px;
            background-color: #fafafa;
        }
        .detail-label {
            font-size: 10px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 12px;
            color: #1a1a1a;
            font-weight: 500;
        }
        
        .defects-list {
            margin-bottom: 20px;
        }
        .defect-item {
            border: 1px solid #ddd;
            margin-bottom: 15px;
            padding: 0;
            page-break-inside: avoid;
        }
        .defect-header {
            background-color: #f0f0f0;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
            font-size: 12px;
        }
        .defect-content {
            padding: 12px;
        }
        .defect-image {
            width: 100%;
            max-width: 400px;
            height: auto;
            margin-bottom: 12px;
            border: 1px solid #ddd;
        }
        .defect-detail {
            margin-bottom: 10px;
            font-size: 11px;
        }
        .defect-detail-label {
            font-weight: bold;
            color: #666;
            display: inline-block;
            width: 160px;
        }
        
        .terms {
            font-size: 10px;
            line-height: 1.5;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .terms h3 {
            font-size: 11px;
            margin-top: 15px;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .terms ol {
            margin-left: 20px;
            margin-bottom: 10px;
        }
        .terms li {
            margin-bottom: 5px;
        }
        
        .signatures {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .signature-block {
            border-top: 1px solid #333;
            padding-top: 10px;
        }
        .signature-block p {
            font-size: 10px;
            margin-top: 3px;
        }
        
        .footer {
            text-align: center;
            font-size: 9px;
            color: #999;
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>DECLARATION OF ADHERENCE AND ACKNOWLEDGEMENT</h1>
            <p>Between the Seller and the Purchaser</p>
            <p>Generated: {{ $generatedDate }} at {{ $generatedTime }}</p>
        </div>

        <!-- Seller Information -->
        <div class="section">
            <div class="section-title">Seller Information</div>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Seller</div>
                    <div class="detail-value">Vantage Ventures Real Estate Development L.L.C.</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Project</div>
                    <div class="detail-value">{{ $booking->unit->property->project_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Property Details -->
        <div class="section">
            <div class="section-title">Property Details</div>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Unit Number</div>
                    <div class="detail-value">{{ $booking->unit->unit ?? 'N/A' }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Building</div>
                    <div class="detail-value">{{ $booking->unit->building ?? 'N/A' }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Floor</div>
                    <div class="detail-value">{{ $booking->unit->floor ?? 'N/A' }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Area</div>
                    <div class="detail-value">{{ $booking->unit->square_footage ?? 'N/A' }} Sq.Ft.</div>
                </div>
            </div>
        </div>

        <!-- Purchaser Details -->
        <div class="section">
            <div class="section-title">Purchaser Information</div>
            @if($booking->user)
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Primary Owner Name</div>
                        <div class="detail-value">{{ $booking->user->full_name ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">{{ $booking->user->email ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value">{{ $booking->user->mobile_number ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Passport Number</div>
                        <div class="detail-value">{{ $booking->user->passport_number ?? 'N/A' }}</div>
                    </div>
                </div>
                @if(count($booking->user->units ?? []) > 1)
                    <div class="section-title" style="margin-top: 15px;">Co-Owners</div>
                    <div class="detail-grid full">
                        @foreach($booking->user->units as $unit)
                            @if($unit->id !== $booking->unit_id)
                                <div class="detail-item">
                                    <div class="detail-label">Co-Owner</div>
                                    <div class="detail-value">{{ $unit->users[0]->full_name ?? 'N/A' }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        <!-- Snagging Defects -->
        @if(count($defects) > 0)
        <div class="section">
            <div class="section-title">Snagging Defects Documented</div>
            <div class="defects-list">
                @foreach($defects as $index => $defect)
                <div class="defect-item">
                    <div class="defect-header">Defect #{{ $index + 1 }}</div>
                    <div class="defect-content">
                        @if($defect->image_url)
                        <img src="{{ $defect->image_url }}" alt="Defect Image" class="defect-image">
                        @endif
                        
                        <div class="defect-detail">
                            <span class="defect-detail-label">Location:</span>
                            <span>{{ $defect->location ?? 'N/A' }}</span>
                        </div>
                        
                        <div class="defect-detail">
                            <span class="defect-detail-label">Description:</span>
                            <span>{{ $defect->description ?? 'N/A' }}</span>
                        </div>
                        
                        <div class="defect-detail">
                            <span class="defect-detail-label">Remediation Action:</span>
                            <span>{{ $defect->agreed_remediation_action ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="section">
            <div class="section-title">Snagging Defects</div>
            <p style="font-size: 12px; color: #666; padding: 15px;">No defects documented.</p>
                <!-- Opening Statement -->
                <div class="section">
                    <p style="font-size: 12px; color: #333; line-height: 1.8;">
                        THIS DECLARATION OF ADHERENCE AND ACKNOWLEDGEMENT is made BETWEEN the Seller and the Purchaser as described in and on the date set out in the Particulars (this Declaration).
                    </p>
                </div>

        </div>
        @endif

        <!-- Background -->
        <div class="section">
            <div class="section-title">Background:</div>
            <p style="font-size: 11px; color: #333; margin-bottom: 8px;"><strong>(A)</strong> The Parties have entered into a sale and purchase agreement (the SPA) under which the Seller agreed to sell and the Purchaser agreed to purchase the Property subject to the terms of the SPA and the Governance Documents.</p>
            
            <p style="font-size: 11px; color: #333; margin-bottom: 8px;"><strong>(B)</strong> In consideration of the Purchaser satisfying its obligations under the SPA, the Seller has handed over the Property to the Purchaser.</p>
            
            <p style="font-size: 11px; color: #333; margin-bottom: 8px;"><strong>(C)</strong> The Purchaser acknowledges handover of the Property upon the conditions set out in the SPA and this Declaration.</p>
            
            <p style="font-size: 11px; color: #333; margin-bottom: 8px;"><strong>(D)</strong> The Purchaser agrees to be bound by the terms of the Governance Documents as further set out in this Declaration.</p>
        </div>

        <!-- Terms & Conditions -->
        <div class="terms">
            <h3>NOW THE PURCHASER AGREES AND DECLARES AS FOLLOWS:</h3>
            
            <h3>1. DEFINITIONS AND INTERPRETATION</h3>
            <p>1.1 In this Declaration, except where the context otherwise requires, the capitalized words shall have the meanings defined in the SPA.</p>
            
            <h3>1.2 ACKNOWLEDGMENT OF PROPERTY</h3>
            <p>1.2 The Purchaser has inspected the Property (or waived its right to inspect the Property) and unconditionally and irrevocably accepts possession of the Property in good condition ready for occupancy and constructed in accordance with the SPA and free from any and all defects and deficiencies (except as listed in the Annexure attached to this Declaration).</p>
            
            <p>1.3 The Purchaser releases and discharges the Seller and its nominees, representatives and subsidiaries (including past, present and future successors, officers, directors, agents and employees) from all claims, damages (including general, special, punitive, liquidated and compensatory damages) and causes of action of every kind, nature and character, known or unknown, fixed or contingent, which the Purchaser may now have or the Purchaser may ever had arising from or in any way connected to the Property.</p>
            
            <p>1.4 The foregoing acceptance, release and discharge is without prejudice to the provisions contained in the SPA regarding rectification of any defects in the Property by the Seller following Handover.</p>
            
            <p>1.5 The Purchaser acknowledges that it is the sole responsibility of the Purchaser to subscribe (register) to and pay all relevant charges in relation to all utilities provided in the Property.</p>
            
            <p>1.6 The Purchaser acknowledges and agrees that all utilities provisions within the Property have been provided and that it is the sole responsibility of the Purchaser that utilities within the Property are available to minimize damage due to the prevailing weather conditions in the UAE.</p>
            
            <p>1.7 The Purchaser acknowledges that leaving the Property not air-conditioned, especially during summer months, may result in damage to the woodwork/joinery, flooring, false ceilings, wall paint and appliances. The Purchaser releases and discharges the Seller and any of its nominees or representatives or subsidiaries from all claims, damages and causes of action arising from this effect.</p>
            
            <h3>1.8 PURCHASER'S COVENANTS AND WARRANTIES</h3>
            <p>The Purchaser covenants and warrants that the Purchaser shall observe, perform and comply with all the terms, conditions and obligations contained in the Governance Documents and the SPA at all times.</p>
            
            <h3>1.9 AUTHORITY TO AMEND</h3>
            <p>1.9 The Purchaser agrees that the Governance Documents may be varied as per their terms or as required to comply with any applicable law or as may be required by the Land Department or RERA from time to time.</p>
            
            <p>1.10 Once notice of any variation of the Governance Documents is served on the Purchaser such variation shall be deemed to be valid, binding and enforceable upon the Purchaser and shall form an integral part of this Declaration.</p>
            
            <h3>1.11 AUTHORITY TO REGISTER</h3>
            <p>The Purchaser agrees that the Governance Documents may be Registered by the Land Department against the title to the Property or the Community or part thereof as a restriction and/or positive covenant.</p>
            
            <h3>1.12 PURCHASER'S INDEMNITY</h3>
            <p>The Purchaser indemnifies the Seller against all actions, costs, claims, damages, demands, expenses, liabilities and losses suffered by the Seller in connection with the Purchaser's breach of its obligations under this Declaration, the SPA and/or the Governance Documents.</p>
            
            <h3>1.13 ACKNOWLEDGMENT OF UNDERSTANDING</h3>
            <p>The Purchaser agrees that it understands the Purchaser's rights and obligations under this Declaration and the Governance Documents.</p>
            
            <h3>1.14 AUTHORITY TO EXECUTE DOCUMENTS</h3>
            <p>The Purchaser warrants and represents that: (a) in the case of the Purchaser being (or including) an individual, the Purchaser has full authority, power and capacity to execute, deliver and perform this Declaration; and (b) in the case of the Purchaser being (or including) an entity other than an individual, the execution, delivery and performance of this Declaration by the Purchaser has been duly authorized in accordance with the relevant corporate or other procedures of the Purchaser, no further action on the part of the Purchaser is necessary to authorize such execution, delivery and performance and the person signing this Declaration on behalf of the Purchaser is fully authorized to enter into this Declaration on behalf of the Purchaser.</p>
            
            <h3>1.15 FURTHER ASSURANCES</h3>
            <p>The Purchaser agrees to immediately sign any documents required by the Land Department and/or RERA as may be necessary to enable Registration of the Governance Documents.</p>
            
            <h3>1.16 CONFIDENTIALITY</h3>
            <p>The Parties must keep the terms of this Declaration and any information provided by the Seller (and/or its Affiliates) strictly confidential.</p>
            
            <h3>1.17 GOVERNING LAW AND JURISDICTION</h3>
            <p>This Declaration and the rights of the Parties set out in it shall be governed by and construed in accordance with the laws of the Emirate of Dubai and the applicable Federal Laws of the UAE. The Parties agree to submit to the exclusive jurisdiction of the Courts of the Emirate of Dubai.</p>
        </div>

        <!-- Signature Section -->
        <div class="signatures">
            <div class="signature-block">
                <p style="font-weight: bold;">Developer Representative</p>
                <p style="margin-top: 30px;">Signature: ___________________</p>
                <p>Name: ___________________</p>
                <p>Date: ___________________</p>
            </div>
            <div class="signature-block">
                <p style="font-weight: bold;">Purchaser</p>
                <p style="margin-top: 30px;">Signature: ___________________</p>
                <p>Name: ___________________</p>
                <p>Date: ___________________</p>
            </div>
        </div>

        <div class="footer">
            <p>This is an electronically generated document. Official record maintained by Vantage Ventures.</p>
        </div>
    </div>
</body>
</html>
