<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f5f5f5; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .logo-header { background-color: #ffffff; padding: 30px 20px; text-align: center; }
        .logo-header img { max-width: 200px; height: auto; }
        .content { padding: 40px 30px; }
        .greeting { font-size: 18px; color: #000000; margin-bottom: 20px; font-weight: 600; }
        .message { color: #333333; font-size: 15px; line-height: 1.8; margin-bottom: 30px; }
        .info-box { background-color: #f9f9f9; border: 2px solid #e0e0e0; padding: 25px; margin: 25px 0; }
        .info-box h3 { color: #000000; font-size: 16px; margin-bottom: 20px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #000000; padding-bottom: 10px; }
        .info-row { padding: 12px 0; border-bottom: 1px solid #e0e0e0; display: table; width: 100%; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #666666; display: table-cell; width: 35%; }
        .info-value { color: #000000; font-weight: 500; display: table-cell; text-align: right; word-break: break-word; }
        .message-box { background-color: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; margin: 25px 0; border-radius: 0 4px 4px 0; }
        .message-box h4 { color: #000000; font-size: 14px; margin-bottom: 10px; font-weight: 600; text-transform: uppercase; }
        .message-box p { color: #333333; font-size: 15px; line-height: 1.7; white-space: pre-wrap; }
        .button { display: inline-block; background-color: #000000; color: #ffffff !important; padding: 15px 35px; text-decoration: none; margin: 25px 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .divider { height: 2px; background-color: #e0e0e0; margin: 30px 0; }
        .footer { background-color: #f9f9f9; padding: 30px; text-align: center; color: #666666; font-size: 13px; border-top: 2px solid #e0e0e0; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="logo-header">
            <img src="https://i.ibb.co/d0QP4tBP/Black-Logo-Zed-Cap.png" alt="Zed Capital" style="max-width: 200px; height: auto;">
        </div>

        <div class="content">
            <div class="greeting">Dear {{ $developerName ?? 'Developer' }},</div>

            <p class="message">{!! $messageBody !!}</p>

            <div class="info-box">
                <h3>{{ $transactionType }} Details</h3>
                @foreach($details as $label => $value)
                    @if($label !== 'Message' && !empty($value))
                    <div class="info-row">
                        <span class="info-label">{{ $label }}:</span>
                        <span class="info-value">{{ $value }}</span>
                    </div>
                    @endif
                @endforeach
            </div>

            <div class="message-box">
                <h4>Admin Message</h4>
                <p>{{ $details['Message'] ?? '' }}</p>
            </div>

            @if(isset($magicLink))
            <div style="text-align: center;">
                <a href="{{ $magicLink }}" class="button">View in Developer Portal</a>
            </div>
            @endif

            <div class="divider"></div>

            <p style="color:#666666; font-size:13px; text-align:center;">
                Log in to the developer portal to reply or upload documents.
            </p>
        </div>

        <div class="footer">
            <p>This is an automated notification from <strong>Zed Capital Finance System</strong>.</p>
            <p style="margin-top:8px;">Please do not reply directly to this email.</p>
        </div>
    </div>
</body>
</html>
