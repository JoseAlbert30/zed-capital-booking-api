@extends('emails.layouts.master')

@section('content')
@php
    $popNumber = $popNumber ?? $soaNumber ?? ($pop->pop_number ?? null) ?? ($soa->soa_number ?? null) ?? 'N/A';
    $unitNumber = $unitNumber ?? ($pop->unit_number ?? null) ?? ($soa->unit_number ?? null) ?? 'N/A';
    $projectName = $projectName ?? ($pop->project_name ?? null) ?? ($soa->project_name ?? null) ?? 'N/A';
    $amount = $amount ?? ($pop->amount ?? null);
    $receiptNumber = $receiptNumber ?? ($pop->receipt_name ?? null);
    $popUrl = $popUrl ?? ($pop->attachment_url ?? null);
    $receiptUrl = $receiptUrl ?? ($pop->receipt_url ?? null);
@endphp

<div class="greeting">
    Dear {{ $developerName }},
</div>

<div class="message-body">
    We are requesting the Statement of Account (SOA) for the following proof of payment:
</div>

<div class="info-box">
    <h3>SOA Request Details</h3>
    <div class="info-row">
        <span class="info-label">POP Number:</span>
        <span class="info-value">{{ $popNumber }}</span>
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
        <span class="info-label">Amount:</span>
        <span class="info-value">
            @if(isset($amount) && is_numeric($amount))
                AED {{ number_format($amount, 2) }}
            @else
                N/A
            @endif
        </span>
    </div>
    <div class="info-row">
        <span class="info-label">Receipt:</span>
        <span class="info-value">{{ $receiptNumber }}</span>
    </div>
</div>

<div class="divider"></div>

<div class="note-box">
    <h4>Reference Documents</h4>
    <ul>
        <li><a href="{{ $popUrl }}" style="color: #1a1a1a; text-decoration: underline;">View Proof of Payment</a></li>
        <li><a href="{{ $receiptUrl }}" style="color: #1a1a1a; text-decoration: underline;">View Receipt</a></li>
    </ul>
</div>

<div class="message-body">
    <strong>Next Steps:</strong> Please access the developer portal to upload the Statement of Account (SOA) document for this payment.
</div>

<div class="button-container">
    <a href="{{ $magicLink }}" class="button">Access Developer Portal</a>
</div>

<div class="note-box">
    <h4>Important Information</h4>
    <ul>
        <li>This secure link is valid for 90 days from the date of this email</li>
        <li>You can upload SOA documents for all pending requests in this project</li>
        <li>No password required - click the link to access directly</li>
        <li>The link provides secure, one-time access to the developer portal</li>
    </ul>
</div>

<div class="signature">
    <strong>Zed Capital - Finance Department</strong>
    For any questions or assistance, please contact our finance team.
</div>
@endsection
