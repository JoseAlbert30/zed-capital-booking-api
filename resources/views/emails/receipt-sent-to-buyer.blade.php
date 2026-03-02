@extends('emails.layouts.master')

@section('content')
<div class="greeting">
    Dear {{ $buyerName }},
</div>

<div class="message-body">
    Your payment receipt is now ready for your records. Please find the details below:
</div>

<div class="info-box">
    <h3>Receipt Details</h3>
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
        <span class="info-label">Receipt Date:</span>
        <span class="info-value">{{ date('F j, Y') }}</span>
    </div>
</div>

<div class="divider"></div>

<div class="button-container">
    <a href="{{ $receiptUrl }}" class="button">Download Receipt</a>
</div>

<div class="message-body">
    Please keep this receipt for your records. If you have any questions regarding this payment, please contact our finance department.
</div>

<div class="note-box">
    <h4>Important</h4>
    <ul>
        <li>This receipt confirms your payment has been processed</li>
        <li>Keep this document for your records</li>
        <li>Contact our finance team if you need any clarification</li>
    </ul>
</div>

<div class="signature">
    <strong>Zed Capital - Finance Team</strong>
    For any questions or assistance, please contact finance@zedcapital.ae
</div>
@endsection
