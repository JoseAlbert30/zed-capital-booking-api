@extends('emails.layouts.master')

@section('content')
<div class="greeting">
    Dear {{ $buyerName }},
</div>

<div class="message-body">
    Please find attached the receipt for the following thirdparty:
</div>

<div class="info-box">
    <h3>Thirdparty Receipt Details</h3>
    <div class="info-row">
        <span class="info-label">Thirdparty Number:</span>
        <span class="info-value">{{ $thirdpartyNumber }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Thirdparty Name:</span>
        <span class="info-value">{{ $thirdpartyName }}</span>
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
        <li>The receipt document is attached to this email</li>
        @if(isset($receiptUrl))
        <li><a href="{{ $receiptUrl }}" style="color: #1a1a1a; text-decoration: underline;">View receipt online</a></li>
        @endif
        <li>Keep this document for your records</li>
    </ul>
</div>

<div class="message-body">
    If you have any questions, please don't hesitate to contact us.
</div>

<div class="signature">
    <strong>Zed Capital Team</strong>
    For any questions or assistance, please contact support@zedcapital.ae
</div>
@endsection
