<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FinanceNOC extends Model
{
    protected $table = 'finance_n_o_c_s';

    protected $fillable = [
        'noc_number',
        'project_name',
        'unit_id',
        'unit_number',
        'noc_name',
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
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
        'document_uploaded_at' => 'datetime',
        'viewed_by_developer' => 'boolean',
        'viewed_at' => 'datetime',
    ];

    /**
     * Get the creator of the NOC
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the unit associated with the NOC
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the document URL attribute
     */
    public function getDocumentUrlAttribute()
    {
        if ($this->document_path) {
            return 'storage/' . $this->document_path;
        }
        return null;
    }

    /**
     * Get the timeline of events for this NOC
     */
    public function getTimelineAttribute()
    {
        $timeline = [];

        // NOC requested
        if ($this->created_at) {
            $timeline[] = [
                'action' => 'NOC Request Created',
                'date' => $this->created_at->timezone('Asia/Dubai')->format('M d, Y'),
                'time' => $this->created_at->timezone('Asia/Dubai')->format('h:i A'),
                'user' => $this->creator ? $this->creator->full_name : 'Admin',
            ];
        }

        // Notification sent
        if ($this->notification_sent_at) {
            $timeline[] = [
                'action' => 'Notification Sent to Developer',
                'date' => $this->notification_sent_at->timezone('Asia/Dubai')->format('M d, Y'),
                'time' => $this->notification_sent_at->timezone('Asia/Dubai')->format('h:i A'),
                'user' => 'System',
            ];
        }

        // Viewed by developer
        if ($this->viewed_at) {
            $timeline[] = [
                'action' => 'Viewed by Developer',
                'date' => $this->viewed_at->timezone('Asia/Dubai')->format('M d, Y'),
                'time' => $this->viewed_at->timezone('Asia/Dubai')->format('h:i A'),
                'user' => 'Developer',
            ];
        }

        // Document uploaded
        if ($this->document_uploaded_at) {
            $timeline[] = [
                'action' => 'NOC Document Uploaded',
                'date' => $this->document_uploaded_at->timezone('Asia/Dubai')->format('M d, Y'),
                'time' => $this->document_uploaded_at->timezone('Asia/Dubai')->format('h:i A'),
                'user' => $this->document_uploaded_by ?? 'Developer',
            ];
        }

        return $timeline;
    }

    /**
     * Boot method to delete file when NOC is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($noc) {
            // Delete document file if exists
            if ($noc->document_path && Storage::disk('public')->exists($noc->document_path)) {
                Storage::disk('public')->delete($noc->document_path);
            }
        });
    }
}
