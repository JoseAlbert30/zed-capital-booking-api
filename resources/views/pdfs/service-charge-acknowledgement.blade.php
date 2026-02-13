<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Service Charge Acknowledgement</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .letterhead {
            width: 100%;
            height: auto;
            display: block;
            margin-bottom: 20px;
        }
        .content {
            padding: 0 40px 40px 40px;
        }
        h1 {
            text-align: center;
            font-size: 14pt;
            margin-bottom: 30px;
            text-decoration: underline;
        }
        p {
            margin: 15px 0;
            text-align: justify;
        }
        .underline {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 200px;
            text-align: center;
        }
        .signature-section {
            margin-top: 40px;
        }
        .signature-block {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            width: 200px;
            display: inline-block;
            margin-left: 10px;
        }
        .label {
            font-weight: bold;
        }
        .dotted-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 150px;
        }
    </style>
</head>
<body>
    @php
        $letterheadPath = storage_path('app/public/letterheads/bcoam-letterhead.png');
    @endphp
    @if(file_exists($letterheadPath))
        <img src="{{ $letterheadPath }}" alt="Letterhead" class="letterhead">
    @endif
    
    <div class="content">
    <h1>Subject: Undertaking letter to pay service charge from February 04, 2026</h1>
    
    <p>Dear Better Communities Owner Association Management,</p>
    
    <p style="margin-top: 30px;">
        I/We 
        @foreach($owners as $index => $owner)
            @if($index > 0 && $index == count($owners) - 1)
                & 
            @elseif($index > 0)
                , 
            @endif
            <span class="underline">{{ $owner->full_name }}</span>, 
            passport number <span class="underline">{{ $owner->passport_number ?? '____________________' }}</span>
        @endforeach
        @if(count($owners) > 1), @endif
        the new buyer(s) of unit <span class="underline">{{ $unit->unit }}</span> in {{ $unit->property->project_name }}, Dubai Production City
    </p>
    
    <p>
        is/are aware that Service Charge is not officially approved by Dubai Land Department as of the request date.
    </p>
    
    <p>
        I/We hereby commit to settle the service charge for the mentioned project as and when issued starting from <strong>February 04, 2026</strong>.
    </p>
    
    <div class="signature-section">
        @foreach($owners as $index => $owner)
            <div class="signature-block">
                <p>Signed by: <span class="signature-line"></span></p>
                <p>Name: <strong>{{ $owner->full_name }}</strong></p>
            </div>
        @endforeach
    </div>
    
    <div style="margin-top: 80px; border-top: 1px dotted #000; padding-top: 20px;">
        <p><strong>Acknowledged By:</strong></p>
        <p style="margin-top: 60px;">____________________________</p>
    </div>
    </div>
</body>
</html>
