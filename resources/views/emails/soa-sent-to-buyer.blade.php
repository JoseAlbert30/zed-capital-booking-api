@extends('emails.layouts.master')

@section('content')
<div class="greeting">
    Dear {{ $buyerName }},
</div>

<div class="message-body">
    Your Statement of Account (SOA) is now ready for your review. Please find the details below:
</div>

<div class="info-box">
    <h3>SOA Document Details</h3>
    <div class="info-row">
        <span class="info-label">SOA Number:</span>
        <span class="info-value">{{ $soaNumber }}</span>
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
    <a href="{{ $documentUrl }}" class="button">Download SOA Document</a>
</div>

<div class="message-body">
    Please review the document carefully. If you have any questions or concerns regarding your statement, please contact our finance department.
</div>

<div class="note-box">
    <h4>Important</h4>
    <ul>
        <li>Please keep this document for your records</li>
        <li>Review all transactions and amounts carefully</li>
        <li>Contact our finance team if you need any clarification</li>
    </ul>
</div>

<div class="signature">
    <strong>Zed Capital - Finance Team</strong>
    For any questions or assistance, please contact finance@zedcapital.ae
</div>
@endsection
