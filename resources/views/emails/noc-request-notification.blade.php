@extends('emails.layouts.master')

@section('content')
<div class="greeting">
    Dear {{ $developerName }},
</div>

<div class="message-body">
    A No Objection Certificate (NOC) has been requested for <strong>Unit {{ $unitNumber }}</strong> in <strong>{{ $projectName }}</strong>.
</div>

<div class="info-box">
    <h3>NOC Request Details</h3>
    <div class="info-row">
        <span class="info-label">NOC Number:</span>
        <span class="info-value">{{ $nocNumber }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">NOC Type:</span>
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

<div class="message-body">
    <strong>Next Steps:</strong> Please access the developer portal to upload the NOC document for this unit.
</div>

<div class="button-container">
    <a href="{{ $magicLink }}" class="button">Access Developer Portal</a>
</div>

<div class="note-box">
    <h4>Important Information</h4>
    <ul>
        <li>This secure link is valid for 90 days from the date of this email</li>
        <li>You can upload NOC documents for all pending requests in this project</li>
        <li>No password required - click the link to access directly</li>
        <li>The buyer will be automatically notified once the NOC is uploaded</li>
    </ul>
</div>

<div class="signature">
    <strong>Zed Capital - Finance Department</strong>
    For any questions or assistance, please contact our finance team.
</div>
@endsection