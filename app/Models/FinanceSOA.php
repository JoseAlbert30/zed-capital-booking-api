<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FinanceSOA extends Model
{
    protected $table = 'finance_s_o_a_s';

    protected $fillable = [
        'soa_number',
        'project_name',
        'unit_id',
        'unit_number',
        'description',
        'document_path',
        'document_name',
        'document_uploaded_at',
        'document_uploaded_by',
        'notification_sent',
        'notification_sent_at',
        'viewed_by_developer',
        'viewed_at',
        'sent_to_buyer',
        'sent_to_buyer_at',
        'sent_to_buyer_email',
        'created_by',
    ];

    protected $casts = [
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
        'document_uploaded_at' => 'datetime',
        'viewed_by_developer' => 'boolean',
        'viewed_at' => 'datetime',
        'sent_to_buyer' => 'boolean',
        'sent_to_buyer_at' => 'datetime',
    ];

    /**
     * Get the creator of the SOA
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the unit associated with the SOA
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
        return $this->document_path;
    }

    /**
     * Get the timeline of events for this SOA
     */
    public function getTimelineAttribute()
    {
        $timeline = [];

        // SOA requested
        if ($this->created_at) {
            $timeline[] = [
                'action' => 'SOA Request Created',
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
                'action' => 'SOA Document Uploaded',
                'date' => $this->document_uploaded_at->timezone('Asia/Dubai')->format('M d, Y'),
                'time' => $this->document_uploaded_at->timezone('Asia/Dubai')->format('h:i A'),
                'user' => $this->document_uploaded_by ?? 'Developer',
            ];
        }

        // Sent to buyer
        if ($this->sent_to_buyer_at) {
            $timeline[] = [
                'action' => 'SOA Sent to Buyer',
                'date' => $this->sent_to_buyer_at->timezone('Asia/Dubai')->format('M d, Y'),
                'time' => $this->sent_to_buyer_at->timezone('Asia/Dubai')->format('h:i A'),
                'user' => 'Admin',
            ];
        }

        return $timeline;
    }

    /**
     * Boot method to delete file when SOA is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($soa) {
            // Delete document file if exists
            if ($soa->document_path && Storage::disk('public')->exists($soa->document_path)) {
                Storage::disk('public')->delete($soa->document_path);
            }
        });
    }
}
