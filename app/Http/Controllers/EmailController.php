<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\EmailLog;
use App\Models\Remark;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class EmailController extends Controller
{
    /**
     * Send email to users with SOA
     * Can handle bulk sending (tested up to 200+ recipients)
     */
    public function sendSOAEmail(Request $request)
    {
        $request->validate([
            'recipient_ids' => 'required|array|min:1|max:200', // Limit to 200 per batch
            'recipient_ids.*' => 'exists:users,id',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string', // Message is optional now (handled by blade template)
        ]);

        try {
            $recipientIds = $request->recipient_ids;
            $subject = $request->subject;
            $message = $request->message ?? ''; // Default to empty string if not provided
            
            $users = User::with(['units.property', 'units.users'])
                ->whereIn('id', $recipientIds)
                ->get();

            $sentCount = 0;
            $failedCount = 0;
            $results = [];

            // Use database transaction for logging
            DB::beginTransaction();

            // Group users by shared units (co-owners should get one email together)
            $processedUserIds = [];
            
            foreach ($users as $user) {
                // Skip if already processed as part of a co-owner group
                if (in_array($user->id, $processedUserIds)) {
                    continue;
                }

                try {
                    // Get co-owners for this user's units
                    $coOwners = collect();
                    foreach ($user->units as $unit) {
                        foreach ($unit->users as $unitUser) {
                            if ($unitUser->id !== $user->id && in_array($unitUser->id, $recipientIds)) {
                                $coOwners->push($unitUser);
                            }
                        }
                    }
                    $coOwners = $coOwners->unique('id');

                    // Combine primary user and co-owners for this email
                    $allRecipients = collect([$user])->merge($coOwners);
                    
                    // Mark all as processed
                    foreach ($allRecipients as $recipient) {
                        $processedUserIds[] = $recipient->id;
                    }

                    // Get uploaded SOA for this unit (check primary user's attachments)
                    $soaAttachment = $user->attachments()->where('type', 'soa')->latest()->first();
                    
                    if (!$soaAttachment) {
                        throw new \Exception("No SOA uploaded for {$user->full_name}. Please upload SOA before sending email.");
                    }

                    // Get the SOA file path - construct from filename pattern
                    $baseName = pathinfo($soaAttachment->filename, PATHINFO_FILENAME);
                    $extension = pathinfo($soaAttachment->filename, PATHINFO_EXTENSION);
                    
                    // Look for files matching pattern: {basename}_{timestamp}.{extension}
                    $storagePath = storage_path('app/public/attachments/');
                    $pattern = $baseName . '_*.'. $extension;
                    $files = glob($storagePath . $pattern);
                    
                    if (empty($files)) {
                            'pattern' => $pattern,
                            'user' => $user->full_name,
                            'attachment_id' => $soaAttachment->id,
                            'filename' => $soaAttachment->filename
                        ]);
                        throw new \Exception("SOA file not found on server for {$user->full_name}. Please re-upload SOA document.");
                    }
                    
                    // Use the most recent matching file
                    $pdfPath = end($files);

                    // Prepare first names for greeting - extract first name from each recipient
                    $firstNames = $allRecipients->map(function($recipient) {
                        return explode(' ', trim($recipient->full_name))[0];
                    })->toArray();
                    
                    // Format the greeting: "Jane", "Jane & John", "Jane, Laura, & Brad"
                    if (count($firstNames) == 1) {
                        $greeting = $firstNames[0];
                    } elseif (count($firstNames) == 2) {
                        $greeting = $firstNames[0] . ' & ' . $firstNames[1];
                    } else {
                        // For 3 or more names: "Jane, Laura, & Brad"
                        $lastNames = array_pop($firstNames);
                        $greeting = implode(', ', $firstNames) . ', & ' . $lastNames;
                    }
                    
                    // Generate public URL for SOA
                    $relativePath = str_replace(storage_path('app/public/'), '', $pdfPath);
                    $soaUrl = url('storage/' . $relativePath);
                    
                    // Send email using blade template with formatted greeting
                    Mail::send('emails.handover-notice', ['firstName' => $greeting, 'soaUrl' => $soaUrl], function ($mail) use ($allRecipients, $subject, $pdfPath, $soaAttachment) {
                        // Add all recipients to the To: field
                        foreach ($allRecipients as $recipient) {
                            $mail->to($recipient->email, $recipient->full_name);
                        }
                        
                        $mail->subject($subject);
                        
                        // Attach uploaded SOA PDF
                        $mail->attach($pdfPath, [
                            'as' => $soaAttachment->filename,
                            'mime' => 'application/pdf',
                        ]);
                    });

                    // Create remarks for ALL recipients (primary + co-owners)
                    foreach ($allRecipients as $recipient) {
                        // Check if initialization email was already sent to this recipient
                        $alreadySent = Remark::where('user_id', $recipient->id)
                            ->where('type', 'email_sent')
                            ->exists();

                        // Mark handover email as sent
                        if (!$recipient->handover_email_sent) {
                            $recipient->handover_email_sent = true;
                            $recipient->handover_email_sent_at = now();
                            $recipient->save();
                        }

                        if ($coOwners->count() > 0) {
                            $coOwnerNames = $coOwners->pluck('full_name')->join(', ');
                            $eventText = $alreadySent 
                                ? "Resent Initialization Email (with co-owners: {$coOwnerNames})"
                                : "Initialization Email sent (with co-owners: {$coOwnerNames})";
                        } else {
                            $eventText = $alreadySent 
                                ? 'Resent Initialization Email (SOA, Handover docs, etc)'
                                : 'Initialization Email sent (SOA, Handover docs, etc)';
                        }
                        
                        // Create remark entry for each recipient
                        Remark::create([
                            'user_id' => $recipient->id,
                            'date' => now()->format('Y-m-d'),
                            'time' => now()->format('H:i:s'),
                            'event' => $eventText,
                            'type' => 'email_sent',
                            'admin_user_id' => $request->user()?->id
                        ]);

                        // Log email for each recipient
                        EmailLog::create([
                            'user_id' => $recipient->id,
                            'recipient_email' => $recipient->email,
                            'recipient_name' => $recipient->full_name,
                            'subject' => $subject,
                            'message' => "Sent to: " . $allRecipients->pluck('email')->join(', '),
                            'email_type' => 'soa',
                            'status' => 'sent',
                            'metadata' => [
                                'co_recipients' => $allRecipients->map(fn($r) => [
                                    'id' => $r->id,
                                    'email' => $r->email,
                                    'name' => $r->full_name
                                ])->toArray()
                            ],
                            'sent_at' => now(),
                        ]);
                    }

                    $sentCount += $allRecipients->count();
                    $results[] = [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'status' => 'success',
                        'recipients' => $allRecipients->pluck('email')->toArray()
                    ];

                } catch (\Exception $e) {
                    $failedCount++;
                    
                    // Log failed email
                    EmailLog::create([
                        'user_id' => $user->id,
                        'recipient_email' => $user->email,
                        'recipient_name' => $user->full_name,
                        'subject' => $subject,
                        'message' => $message,
                        'email_type' => 'soa',
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'metadata' => ['error_trace' => $e->getTraceAsString()],
                        'sent_at' => null,
                    ]);
                    
                    $results[] = [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                    
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Email processing complete. Sent: {$sentCount}, Failed: {$failedCount}",
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'total_recipients' => count($users),
                'results' => $results
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to send emails',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Personalize email message with user data
     */
    private function personalizeMessage(string $message, User $user): string
    {
        $units = $user->units->pluck('unit')->join(', ');
        $projects = $user->units->pluck('property.project_name')->unique()->join(', ');
        
        $personalizedMessage = str_replace('[Name]', $user->full_name, $message);
        $personalizedMessage = str_replace('[Unit]', $units ?: 'N/A', $personalizedMessage);
        $personalizedMessage = str_replace('[Project]', $projects ?: 'N/A', $personalizedMessage);
        
        return $personalizedMessage;
    }

    /**
     * Get email logs for a user
     */
    public function getUserEmailLogs(Request $request, $userId)
    {
        $logs = EmailLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    /**
     * Get all email logs (admin only)
     */
    public function getAllEmailLogs(Request $request)
    {
        $logs = EmailLog::with('user:id,full_name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($logs);
    }

    /**
     * Generate SOA PDF for a user
     */
    private function generateSOAPDF(User $user): string
    {
        $units = $user->units;
        $firstUnit = $units->first();
        $unitNumbers = $units->pluck('unit')->join(', ');
        $projects = $units->pluck('property.project_name')->unique()->join(', ');
        
        // Create PDF content
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 40px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 3px solid #000;
                    padding-bottom: 20px;
                }
                .header h1 {
                    margin: 0;
                    color: #000;
                }
                .header p {
                    margin: 5px 0;
                    color: #666;
                }
                .content {
                    margin-top: 30px;
                }
                .info-row {
                    margin: 15px 0;
                    padding: 10px;
                    background: #f5f5f5;
                }
                .info-row label {
                    font-weight: bold;
                    display: inline-block;
                    width: 150px;
                }
                .info-row span {
                    color: #333;
                }
                .footer {
                    margin-top: 50px;
                    padding-top: 20px;
                    border-top: 2px solid #000;
                    text-align: center;
                    color: #666;
                    font-size: 12px;
                }
                .watermark {
                    text-align: center;
                    color: #ddd;
                    font-size: 48px;
                    font-weight: bold;
                    margin: 30px 0;
                    transform: rotate(-15deg);
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>ZED CAPITAL</h1>
                <p>Statement of Account</p>
                <p>Document #: SOA-' . date('Ymd') . '-' . $user->id . '</p>
            </div>
            
            <div class="content">
                <div class="info-row">
                    <label>Client Name:</label>
                    <span>' . htmlspecialchars($user->full_name) . '</span>
                </div>
                
                <div class="info-row">
                    <label>Email:</label>
                    <span>' . htmlspecialchars($user->email) . '</span>
                </div>
                
                <div class="info-row">
                    <label>Mobile Number:</label>
                    <span>' . htmlspecialchars($user->mobile_number ?? 'N/A') . '</span>
                </div>
                
                <div class="info-row">
                    <label>Unit(s):</label>
                    <span>' . htmlspecialchars($unitNumbers) . '</span>
                </div>
                
                <div class="info-row">
                    <label>Project(s):</label>
                    <span>' . htmlspecialchars($projects) . '</span>
                </div>
                
                <div class="info-row">
                    <label>Payment Status:</label>
                    <span>' . strtoupper(str_replace('_', ' ', $user->payment_status)) . '</span>
                </div>
                
                <div class="info-row">
                    <label>Payment Date:</label>
                    <span>' . ($user->payment_date ? date('F d, Y', strtotime($user->payment_date)) : 'Pending') . '</span>
                </div>
                
                <div class="watermark">SAMPLE DOCUMENT</div>
                
                <p style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                    <strong>Note:</strong> This is a sample Statement of Account generated for email purposes. 
                    For official documentation, please contact Zed Capital management.
                </p>
            </div>
            
            <div class="footer">
                <p>Generated on: ' . date('F d, Y H:i:s') . '</p>
                <p>Zed Capital Booking System | Contact: info@zedcapital.com</p>
            </div>
        </body>
        </html>
        ';
        
        // Generate PDF
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        // Save PDF to storage
        $filename = 'SOA_' . ($firstUnit ? $firstUnit->unit : 'Unit') . '_' . $user->id . '.pdf';
        $filepath = storage_path('app/soa/' . $filename);
        
        $pdf->save($filepath);
        
        return $filepath;
    }
}
