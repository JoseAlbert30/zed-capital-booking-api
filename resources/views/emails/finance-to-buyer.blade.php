<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .logo-header {
            background-color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }

        .logo-header img {
            max-width: 200px;
            height: auto;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 18px;
            color: #000000;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .message {
            color: #333333;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .info-box {
            background-color: #f9f9f9;
            border: 2px solid #e0e0e0;
            padding: 25px;
            margin: 25px 0;
        }

        .info-box h3 {
            color: #000000;
            font-size: 16px;
            margin-bottom: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #000000;
            padding-bottom: 10px;
        }

        .info-row {
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
            display: table;
            width: 100%;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #666666;
            display: table-cell;
            width: 45%;
        }

        .info-value {
            color: #000000;
            font-weight: 500;
            display: table-cell;
            text-align: right;
            word-break: break-word;
        }

        .button {
            display: inline-block;
            background-color: #000000;
            color: #ffffff !important;
            padding: 15px 35px;
            text-decoration: none;
            margin: 25px 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .divider {
            height: 2px;
            background-color: #e0e0e0;
            margin: 30px 0;
        }

        .footer {
            background-color: #f9f9f9;
            padding: 30px;
            text-align: center;
            color: #666666;
            font-size: 13px;
            border-top: 2px solid #e0e0e0;
        }

        .note-box {
            background-color: #fff8e1;
            border-left: 4px solid #ffa000;
            padding: 20px;
            margin: 25px 0;
        }

        .note-box h4 {
            color: #000000;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .note-box ul {
            margin: 10px 0 0 20px;
            padding: 0;
            color: #666666;
        }
    </style>
</head>

<body>
    @php
    $logoPath = public_path('storage/letterheads/zed.png');
    $logoBase64 = '';
    if (file_exists($logoPath)) {
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }
    @endphp
    <div class="email-container">
        <!-- Logo Header -->
        <div class="logo-header">
            <img src="{{ $logoBase64 ?: asset('storage/letterheads/zed.png') }}" alt="Zed Capital"
                style="max-width: 200px; height: auto;">
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">Dear {{ $buyerName ?? 'Valued Client' }},</div>

            <p class="message">
                {{ $messageBody ?? 'We are writing to inform you about an important update regarding your property.' }}
            </p>

            <div class="info-box">
                <h3>{{ $transactionType }} Information</h3>
                @foreach($details as $label => $value)
                <div class="info-row">
                    <span class="info-label">{{ $label }}:</span>
                    <span class="info-value">{{ $value }}</span>
                </div>
                @endforeach
            </div>

            @if(isset($buttonUrl) && isset($buttonText))
            <div style="text-align: center;">
                <a href="{{ $buttonUrl }}" class="button">{{ $buttonText }}</a>
            </div>
            @endif

            <div class="divider"></div>

            @isset($additionalInfo)
            <div class="note-box">
                <h4>{{ $additionalInfo['title'] ?? 'Important Information' }}:</h4>
                @if(isset($additionalInfo['points']) && is_array($additionalInfo['points']))
                <ul>
                    @foreach($additionalInfo['points'] as $point)
                    <li>{{ $point }}</li>
                    @endforeach
                </ul>
                @else
                <p style="margin: 0; color: #666666;">
                    {{ $additionalInfo['message'] ?? '' }}
                </p>
                @endif
            </div>
            @endisset

            <p class="message" style="margin-top: 30px;">
                @isset($closingMessage)
                {{ $closingMessage }}
                @else
                If you have any questions or concerns, please don't hesitate to contact our finance team.
                @endisset
            </p>

            <p class="message">
                Best regards,<br>
                <strong>Zed Capital Finance Team</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Zed Capital Real Estate</strong></p>
        </div>
    </div>
</body>

</html>