@extends('emails.layouts.master')

@section('content')
<div class="greeting">
    Dear {{ $magicLink->developer_name ?? 'Developer' }},
</div>

<div class="message-body">
    We have received a new proof of payment for <strong>{{ $pop->project_name }}</strong>. Please review and process this payment at your earliest convenience.
</div>

<div class="info-box">
    <h3>Payment Details</h3>
    <div class="info-row">
        <span class="info-label">POP Number:</span>
        <span class="info-value">{{ $pop->pop_number }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Unit Number:</span>
        <span class="info-value">{{ $pop->unit_number }}</span>
    </div>
    @if(isset($pop->amount))
    <div class="info-row">
        <span class="info-label">Amount:</span>
        <span class="info-value">AED {{ number_format($pop->amount, 2) }}</span>
    </div>
    @endif
    <div class="info-row">
        <span class="info-label">Date Received:</span>
        <span class="info-value">{{ $pop->created_at->format('F d, Y') }}</span>
    </div>
</div>

<div class="divider"></div>

<div class="message-body">
    <strong>Next Steps:</strong> Please access the developer portal to review the proof of payment document and upload the official receipt for the buyer.
</div>

<div class="button-container">
    <a href="{{ $portalUrl }}" class="button">Access Developer Portal</a>
</div>

<div class="note-box">
    <h4>Important Information</h4>
    <ul>
        <li>This secure link is valid for 90 days from the date of this email</li>
        <li>You can upload receipts for all pending POPs in this project</li>
        <li>No password required - click the link to access directly</li>
        <li>The link provides secure, one-time access to the developer portal</li>
    </ul>
</div>

<div class="signature">
    <strong>Zed Capital - Finance Department</strong>
    For any questions or assistance, please contact our finance team.
</div>
@endsection
