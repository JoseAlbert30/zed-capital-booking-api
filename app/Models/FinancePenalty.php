<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FinancePenalty extends Model
{
    protected $fillable = [
        'penalty_number',
        'project_name',
        'unit_id',
        'unit_number',
        'penalty_name',
        'description',
        'document_path',
        'document_name',
        'document_uploaded_at',
        'document_uploaded_by',
        'proof_of_payment_path',
        'proof_of_payment_name',
        'proof_of_payment_uploaded_at',
        'receipt_path',
        'receipt_name',
        'receipt_uploaded_at',
        'receipt_sent_to_buyer',
        'receipt_sent_to_buyer_at',
        'receipt_sent_to_buyer_email',
        'sent_to_buyer',
        'sent_to_buyer_at',
        'sent_to_buyer_email',
        'notification_sent',
        'notification_sent_at',
        'viewed_by_developer',
        'viewed_by_admin',
        'viewed_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
        'document_uploaded_at' => 'datetime',
        'proof_of_payment_uploaded_at' => 'datetime',
        'receipt_uploaded_at' => 'datetime',
        'receipt_sent_to_buyer' => 'boolean',
        'receipt_sent_to_buyer_at' => 'datetime',
        'sent_to_buyer' => 'boolean',
        'sent_to_buyer_at' => 'datetime',
        'viewed_by_developer' => 'boolean',
        'viewed_by_admin' => 'boolean',
        'viewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['document_url', 'proof_of_payment_url', 'receipt_url', 'timeline'];

    /**
     * Get the user who created this penalty
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the unit associated with this penalty
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the property/project associated with this penalty
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'project_name', 'project_name');
    }

    /**
     * Get the attachments for this penalty
     */
    public function attachments()
    {
        return $this->morphMany(FinanceAttachment::class, 'attachable');
    }

    /**
     * Get the document URL
     */
    public function getDocumentUrlAttribute()
    {
        return $this->document_path;
    }

    /**
     * Get the proof of payment URL
     */
    public function getProofOfPaymentUrlAttribute()
    {
        return $this->proof_of_payment_path ? Storage::disk('public')->url($this->proof_of_payment_path) : null;
    }

    /**
     * Get the receipt URL
     */
    public function getReceiptUrlAttribute()
    {
        return $this->receipt_path ? Storage::disk('public')->url($this->receipt_path) : null;
    }

    /**
     * Get the timeline of events
     */
    public function getTimelineAttribute()
    {
        $timeline = [];
        
        // Get property to check penalty_initiated_by
        $property = $this->property;
        $penaltyInitiatedBy = $property->penalty_initiated_by ?? 'admin';

        /**
         * WORKFLOW:
         * 
         * Developer-initiated:
         * 1. Developer creates penalty statement
         * 2. Admin uploads proof of payment
         * 3. Developer uploads receipt
         * 4. Admin sends receipt to buyer
         * 
         * Admin-initiated:
         * 1. Admin creates penalty statement (with proof of payment already included)
         * 2. Developer uploads receipt
         * 3. Admin sends receipt to buyer
         */

        // Step 1: Penalty Created
        if ($this->created_at) {
            $createdBy = $this->creator ? $this->creator->full_name : ($penaltyInitiatedBy === 'admin' ? 'Admin' : 'Developer');
            
            if ($penaltyInitiatedBy === 'developer') {
                // Developer creates penalty statement
                $timeline[] = [
                    'action' => 'Penalty Statement Created by Developer',
                    'date' => $this->created_at->timezone('Asia/Dubai')->format('Y-m-d'),
                    'time' => $this->created_at->timezone('Asia/Dubai')->format('H:i:s'),
                    'user' => $createdBy,
                ];
            } else {
                // Admin creates penalty (with proof already)
                $timeline[] = [
                    'action' => 'Penalty Statement Created by Admin',
                    'date' => $this->created_at->timezone('Asia/Dubai')->format('Y-m-d'),
                    'time' => $this->created_at->timezone('Asia/Dubai')->format('H:i:s'),
                    'user' => $createdBy,
                ];
            }
        }

        // Step 2: Notification sent to other party
        if ($this->notification_sent_at) {
            $sentTo = $penaltyInitiatedBy === 'admin' ? 'Developer' : 'Admin';
            
            $timeline[] = [
                'action' => "Sent to $sentTo",
                'date' => $this->notification_sent_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->notification_sent_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $penaltyInitiatedBy === 'admin' ? 'Admin' : 'Developer',
            ];
        }

        // Step 3: Viewed by other party
        if ($this->viewed_at) {
            if ($penaltyInitiatedBy === 'admin' && $this->viewed_by_developer) {
                $timeline[] = [
                    'action' => 'Viewed by Developer',
                    'date' => $this->viewed_at->timezone('Asia/Dubai')->format('Y-m-d'),
                    'time' => $this->viewed_at->timezone('Asia/Dubai')->format('H:i:s'),
                    'user' => 'Developer',
                ];
            } elseif ($penaltyInitiatedBy === 'developer' && $this->viewed_by_admin) {
                $timeline[] = [
                    'action' => 'Viewed by Admin',
                    'date' => $this->viewed_at->timezone('Asia/Dubai')->format('Y-m-d'),
                    'time' => $this->viewed_at->timezone('Asia/Dubai')->format('H:i:s'),
                    'user' => 'Admin',
                ];
            }
        }

        // Step 4: Proof of Payment Uploaded (Admin uploads - for developer-initiated OR during creation for admin-initiated)
        if ($this->proof_of_payment_uploaded_at) {
            $timeline[] = [
                'action' => 'Proof of Payment Uploaded',
                'date' => $this->proof_of_payment_uploaded_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->proof_of_payment_uploaded_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => 'Admin',
            ];
        }

        // Step 5: Receipt Uploaded (Developer uploads)
        if ($this->receipt_uploaded_at) {
            $timeline[] = [
                'action' => 'Receipt Uploaded',
                'date' => $this->receipt_uploaded_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->receipt_uploaded_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => 'Developer',
            ];
        }

        // Step 5.5: Invoice/Penalty Sent to Buyer (can happen after document upload or proof upload)
        if ($this->sent_to_buyer_at) {
            $timeline[] = [
                'action' => 'Invoice Sent to Buyer',
                'date' => $this->sent_to_buyer_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->sent_to_buyer_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => 'Admin',
            ];
        }

        // Step 6: Receipt Sent to Buyer (Admin sends)
        if ($this->receipt_sent_to_buyer_at) {
            $timeline[] = [
                'action' => 'Receipt Sent to Buyer',
                'date' => $this->receipt_sent_to_buyer_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->receipt_sent_to_buyer_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => 'Admin',
            ];
        }

        return $timeline;
    }

    /**
     * Delete document file when deleting the model
     */
    protected static function booted()
    {
        static::deleting(function ($penalty) {
            if ($penalty->document_path && Storage::disk('public')->exists($penalty->document_path)) {
                Storage::disk('public')->delete($penalty->document_path);
            }
            if ($penalty->proof_of_payment_path && Storage::disk('public')->exists($penalty->proof_of_payment_path)) {
                Storage::disk('public')->delete($penalty->proof_of_payment_path);
            }
            if ($penalty->receipt_path && Storage::disk('public')->exists($penalty->receipt_path)) {
                Storage::disk('public')->delete($penalty->receipt_path);
            }
        });
    }
}
