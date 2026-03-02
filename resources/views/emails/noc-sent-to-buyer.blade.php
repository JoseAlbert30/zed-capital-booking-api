@extends('emails.layouts.master')

@section('content')
<div class="greeting">
    Dear {{ $buyerName }},
</div>

<div class="message-body">
    Your No Objection Certificate (NOC) is now ready for your review. Please find the details below:
</div>

<div class="info-box">
    <h3>NOC Document Details</h3>
    <div class="info-row">
        <span class="info-label">NOC Number:</span>
        <span class="info-value">{{ $nocNumber }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">NOC Name:</span>
        <span class="info-value">{{ $nocName }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Unit Number:</span>
        <span class="info-value">{{ $unitNumber }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Project:</span>
        <span class="info-value">{{ $projectName }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Document Date:</span>
        <span class="info-value">{{ date('F j, Y') }}</span>
    </div>
</div>

<div class="divider"></div>

<div class="button-container">
    <a href="{{ $documentUrl }}" class="button">Download NOC Document</a>
</div>

<div class="message-body">
    Please review the document carefully. If you have any questions or concerns regarding this NOC, please contact our office.
</div>

<div class="note-box">
    <h4>Important</h4>
    <ul>
        <li>Please keep this document for your records</li>
        <li>This NOC is specific to your unit and requested purpose</li>
        <li>Contact our team if you need any clarification</li>
    </ul>
</div>

<div class="signature">
    <strong>Zed Capital Team</strong>
    For any questions or assistance, please contact office@zedcapital.ae
</div>
@endsection