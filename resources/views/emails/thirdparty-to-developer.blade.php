@extends('emails.layouts.master')

@section('content')
<div class="greeting">
    Hello {{ $developerName }},
</div>

<div class="message-body">
    A signed thirdparty document has been received from the buyer and requires your attention.
</div>

<div class="info-box">
    <h3>Thirdparty Document Details</h3>
    <div class="info-row">
        <span class="info-label">Thirdparty Number:</span>
        <span class="info-value">{{ $thirdparty->thirdparty_number }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Name:</span>
        <span class="info-value">{{ $thirdparty->thirdparty_name }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Project:</span>
        <span class="info-value">{{ $thirdparty->project_name }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Unit Number:</span>
        <span class="info-value">{{ $thirdparty->unit_number }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Date:</span>
        <span class="info-value">{{ \Carbon\Carbon::parse($thirdparty->created_at)->format('F j, Y g:i A') }}</span>
    </div>
    @if($thirdparty->description)
    <div class="info-row">
        <span class="info-label">Description:</span>
        <span class="info-value">{{ $thirdparty->description }}</span>
    </div>
    @endif
    @if($thirdparty->notes)
    <div class="info-row">
        <span class="info-label">Notes:</span>
        <span class="info-value">{{ $thirdparty->notes }}</span>
    </div>
    @endif
</div>

@if($thirdparty->signed_document_url || $thirdparty->attachments->count() > 0)
<div class="divider"></div>

<div class="note-box">
    <h4>Attached Documents</h4>
    <ul>
        @if($thirdparty->signed_document_url)
        <li><a href="{{ $thirdparty->signed_document_url }}" target="_blank" style="color: #1a1a1a; text-decoration: underline;">Signed Document</a></li>
        @endif
        @foreach($thirdparty->attachments as $attachment)
        <li><a href="{{ $attachment->file_url }}" target="_blank" style="color: #1a1a1a; text-decoration: underline;">{{ $attachment->file_name }}</a></li>
        @endforeach
    </ul>
</div>
@endif

<div class="note-box" style="background-color: #fef3c7; border: 1px solid #fcd34d;">
    <h4>⚠️ Required Action</h4>
    <ul>
        <li>Review the signed thirdparty document and all attachments</li>
        <li>Process the thirdparty request as per your procedures</li>
        <li>Upload the receipt document once completed</li>
        <li>The admin will be notified upon receipt upload</li>
    </ul>
</div>

<div class="button-container">
    <a href="{{ config('app.url') }}/projects/{{ urlencode($thirdparty->project_name) }}" class="button">View & Upload Receipt</a>
</div>

<div class="message-body" style="color: #6b7280; font-size: 14px;">
    Please process this thirdparty request at your earliest convenience. If you have any questions or concerns, please contact the admin team.
</div>

<div class="signature">
    <strong>Zed Capital - Operations Team</strong>
    For any questions or assistance, please contact our operations team.
</div>
@endsection
