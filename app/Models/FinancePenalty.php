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
        'amount',
        'description',
        'document_path',
        'document_name',
        'document_uploaded_at',
        'document_uploaded_by',
        'notification_sent',
        'notification_sent_at',
        'viewed_by_developer',
        'viewed_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
        'document_uploaded_at' => 'datetime',
        'viewed_by_developer' => 'boolean',
        'viewed_at' => 'datetime',
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

        if ($this->created_at) {
            $timeline[] = [
                'action' => 'Penalty Requested',
                'date' => $this->created_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->created_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $this->creator ? $this->creator->full_name : 'Admin',
            ];
        }

        if ($this->notification_sent_at) {
            $timeline[] = [
                'action' => 'Sent to Developer',
                'date' => $this->notification_sent_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->notification_sent_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $this->creator ? $this->creator->full_name : 'Admin',
            ];
        }

        if ($this->viewed_at) {
            $timeline[] = [
                'action' => 'Viewed by Developer',
                'date' => $this->viewed_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->viewed_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => 'Developer',
            ];
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
