@extends('emails.layouts.master')

@section('content')
<div class="greeting">
    Dear {{ $developerName }},
</div>

<div class="message-body">
    A penalty has been issued for <strong>Unit {{ $unitNumber }}</strong> in <strong>{{ $projectName }}</strong>.
</div>

<div class="info-box">
    <h3>Penalty Details</h3>
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
    <div class="info-row">
        <span class="info-label">Amount:</span>
        <span class="info-value" style="font-size: 18px; font-weight: 700; color: #991b1b;">AED {{ number_format($amount, 2) }}</span>
    </div>
</div>

@if($description)
<div class="divider"></div>

<div class="note-box">
    <h4>Description</h4>
    <ul>
        <li>{{ $description }}</li>
    </ul>
</div>
@endif

<div class="message-body" style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
    <strong>⚠️ Action Required:</strong> Please upload the penalty document through the developer portal.
</div>

<div class="button-container">
    <a href="{{ $magicLink }}" class="button">Access Developer Portal</a>
</div>

<div class="note-box">
    <h4>Important Information</h4>
    <ul>
        <li>This secure link is valid for 90 days from the date of this email</li>
        <li>You can upload penalty documents for all pending requests in this project</li>
        <li>No password required - click the link to access directly</li>
        <li>The buyer will be automatically notified once the penalty document is uploaded</li>
    </ul>
</div>

<div class="signature">
    <strong>Zed Capital - Finance Department</strong>
    For any questions or assistance, please contact our finance team.
</div>
@endsection
