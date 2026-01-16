<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ZedCapital Booking</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); padding: 40px 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">Welcome to ZedCapital Booking</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="color: #334155; font-size: 16px; line-height: 1.6; margin-top: 0;">
                                Dear {{ $user->full_name }},
                            </p>
                            
                            <p style="color: #334155; font-size: 16px; line-height: 1.6;">
                                Your account has been created successfully for unit <strong>{{ $unit->unit }}</strong> at <strong>{{ $unit->property->project_name }}</strong>.
                            </p>
                            
                            <div style="background-color: #f8fafc; border-left: 4px solid #3b82f6; padding: 20px; margin: 30px 0; border-radius: 4px;">
                                <h3 style="color: #1e293b; margin-top: 0; font-size: 18px;">Your Login Credentials</h3>
                                <p style="color: #334155; font-size: 16px; margin: 10px 0;">
                                    <strong>Email:</strong> {{ $user->email }}
                                </p>
                                <p style="color: #334155; font-size: 16px; margin: 10px 0;">
                                    <strong>Password:</strong> <code style="background-color: #e5e7eb; padding: 4px 8px; border-radius: 4px; font-family: monospace;">{{ $password }}</code>
                                </p>
                            </div>
                            
                            <p style="color: #334155; font-size: 16px; line-height: 1.6;">
                                You can now log in to the ZedCapital Booking portal to:
                            </p>
                            
                            <ul style="color: #334155; font-size: 16px; line-height: 1.8;">
                                <li>Schedule property visits</li>
                                <li>Upload payment receipts and documents</li>
                                <li>Track your booking status</li>
                                <li>View handover requirements</li>
                                <li>Communicate with our team</li>
                            </ul>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{ config('app.frontend_url') }}/login" style="display: inline-block; background-color: #3b82f6; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold;">
                                    Login to Your Account
                                </a>
                            </div>
                            
                            <div style="background-color: #fef3c7; border: 1px solid #fbbf24; padding: 15px; margin: 30px 0; border-radius: 4px;">
                                <p style="color: #92400e; font-size: 14px; margin: 0;">
                                    <strong>⚠️ Security Notice:</strong> Please change your password after your first login for security purposes.
                                </p>
                            </div>
                            
                            <p style="color: #334155; font-size: 16px; line-height: 1.6;">
                                If you have any questions or need assistance, please don't hesitate to contact our support team.
                            </p>
                            
                            <p style="color: #334155; font-size: 16px; line-height: 1.6; margin-bottom: 0;">
                                Best regards,<br>
                                <strong>The ZedCapital Team</strong>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 30px; text-align: center; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb;">
                            <p style="color: #64748b; font-size: 14px; margin: 0;">
                                This is an automated message. Please do not reply to this email.
                            </p>
                            <p style="color: #64748b; font-size: 12px; margin: 10px 0 0 0;">
                                © {{ date('Y') }} ZedCapital. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
