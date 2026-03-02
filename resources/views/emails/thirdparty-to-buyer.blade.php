@extends('emails.layouts.master')

@section('content')
<div class="greeting">
    Dear {{ $buyerName }},
</div>

<div class="message-body">
    A thirdparty form requires your attention and signature. Please review the details below:
</div>

<div class="info-box">
    <h3>Thirdparty Form Details</h3>
    <div class="info-row">
        <span class="info-label">Form Number:</span>
        <span class="info-value">{{ $thirdpartyNumber }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Form Name:</span>
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
    <div class="info-row">
        <span class="info-label">Date:</span>
        <span class="info-value">{{ date('F j, Y') }}</span>
    </div>
</div>

<div class="divider"></div>

<div class="button-container">
    <a href="{{ $documentUrl }}" class="button">Download Form</a>
</div>

<div class="note-box" style="background-color: #fef3c7; border-left: 4px solid #f59e0b;">
    <h4>Important Instructions</h4>
    <ul>
        <li>Download and review the form carefully</li>
        <li>Print and sign the document where indicated</li>
        <li>Scan the signed document</li>
        <li>Reply to this email with the signed document attached</li>
    </ul>
</div>

<div class="message-body">
    Once we receive your signed form, we will process it with the developer and keep you updated on the progress.
</div>

<div class="message-body">
    If you have any questions or need assistance, please don't hesitate to contact our finance team.
</div>

<div class="signature">
    <strong>Zed Capital Finance Team</strong>
    For any questions or assistance, please contact finance@zedcapital.ae
</div>
@endsection
