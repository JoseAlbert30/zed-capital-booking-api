@extends('emails.layouts.master')

@section('content')
<div class="greeting">
    Dear {{ $buyerName }},
</div>

<div class="message-body">
    We are pleased to confirm that the penalty payment for your unit has been processed. Please find the receipt details below:
</div>

<div class="info-box">
    <h3>Penalty Receipt Details</h3>
    <div class="info-row">
        <span class="info-label">Penalty Number:</span>
        <span class="info-value">{{ $penaltyNumber }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Penalty Name:</span>
        <span class="info-value">{{ $penaltyName }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Unit Number:</span>
        <span class="info-value">{{ $unitNumber }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Project:</span>
        <span class="info-value">{{ $projectName }}</span>
    </div>
</div>

<div class="divider"></div>

<div class="note-box">
    <h4>Receipt Document</h4>
    <ul>
        <li>Your payment receipt is attached to this email</li>
        <li>You can also download it using the button below</li>
        <li>Keep this document for your records</li>
    </ul>
</div>

<div class="button-container">
    <a href="{{ $receiptUrl }}" class="button">Download Receipt</a>
</div>

<div class="message-body">
    Please keep this receipt for your records. If you have any questions regarding this payment, please contact our customer service team.
</div>

<div class="message-body">
    Thank you for your cooperation.
</div>

<div class="signature">
    <strong>Zed Capital Team</strong>
    {{ $projectName }}
</div>
@endsection
