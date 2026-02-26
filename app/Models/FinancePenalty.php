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
        'notification_sent',
        'notification_sent_at',
        'viewed_by_developer',
        'viewed_by_admin',
        'viewed_at',
        'sent_to_buyer',
        'sent_to_buyer_at',
        'sent_to_buyer_email',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
        'document_uploaded_at' => 'datetime',
        'viewed_by_developer' => 'boolean',
        'viewed_by_admin' => 'boolean',
        'viewed_at' => 'datetime',
        'sent_to_buyer' => 'boolean',
        'sent_to_buyer_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['document_url', 'timeline'];

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
     * Get the timeline of events
     */
    public function getTimelineAttribute()
    {
        $timeline = [];
        
        // Get property to check penalty_initiated_by
        $property = $this->property;
        $penaltyInitiatedBy = $property->penalty_initiated_by ?? 'admin';

        if ($this->created_at) {
            $createdBy = $this->creator ? $this->creator->full_name : 'Admin';
            $requestText = $penaltyInitiatedBy === 'admin' ? 'Penalty Requested' : 'Penalty Submitted';
            
            $timeline[] = [
                'action' => $requestText,
                'date' => $this->created_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->created_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $createdBy,
            ];
        }

        if ($this->notification_sent_at) {
            // If admin created, sent to developer. If developer created, sent to admin
            $sentTo = $penaltyInitiatedBy === 'admin' ? 'Developer' : 'Admin';
            $sentBy = $this->creator ? $this->creator->full_name : 'System';
            
            $timeline[] = [
                'action' => "Sent to $sentTo",
                'date' => $this->notification_sent_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->notification_sent_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $sentBy,
            ];
        }

        if ($this->viewed_at) {
            // Show who viewed it based on the flags
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

        if ($this->document_uploaded_at) {
            $timeline[] = [
                'action' => 'Penalty Document Uploaded',
                'date' => $this->document_uploaded_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->document_uploaded_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $this->document_uploaded_by ?: 'Developer',
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
        });
    }
}
