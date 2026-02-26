<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FinancePOP extends Model
{
    use HasFactory;

    protected $table = 'finance_pops';

    protected $fillable = [
        'pop_number',
        'project_name',
        'unit_id',
        'unit_number',
        'attachment_path',
        'attachment_name',
        'notification_sent',
        'notification_sent_at',
        'viewed_by_developer',
        'viewed_at',
        'receipt_path',
        'receipt_name',
        'receipt_uploaded_at',
        'receipt_uploaded_by',
        'receipt_sent_to_buyer',
        'receipt_sent_to_buyer_at',
        'receipt_sent_to_buyer_email',
        'buyer_email',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
        'viewed_by_developer' => 'boolean',
        'viewed_at' => 'datetime',
        'receipt_uploaded_at' => 'datetime',
        'receipt_sent_to_buyer' => 'boolean',
        'receipt_sent_to_buyer_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['attachment_url', 'receipt_url', 'soa_docs_url', 'timeline'];

    /**
     * Get the user who created this POP
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the unit associated with this POP
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the full URL for the attachment
     */
    public function getAttachmentUrlAttribute()
    {
        return $this->attachment_path;
    }

    /**
     * Get the receipt URL.
     */
    public function getReceiptUrlAttribute()
    {
        return $this->receipt_path;
    }

    /**
     * Get the timeline of events.
     */
    public function getTimelineAttribute()
    {
        $timeline = [];

        if ($this->created_at) {
            $timeline[] = [
                'action' => 'POP Uploaded',
                'date' => $this->created_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->created_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $this->creator ? $this->creator->full_name : 'System',
            ];
        }

        if ($this->notification_sent_at) {
            $timeline[] = [
                'action' => 'Sent to Developer',
                'date' => $this->notification_sent_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->notification_sent_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $this->creator ? $this->creator->full_name : 'System',
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

        if ($this->receipt_uploaded_at) {
            $timeline[] = [
                'action' => 'Receipt Uploaded',
                'date' => $this->receipt_uploaded_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->receipt_uploaded_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $this->receipt_uploaded_by ?: 'Developer',
            ];
        }

        if ($this->soa_requested_at) {
            $timeline[] = [
                'action' => 'Requested SOA from Developer',
                'date' => $this->soa_requested_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->soa_requested_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $this->creator ? $this->creator->full_name : 'Admin',
            ];
        }

        if ($this->soa_docs_uploaded_at) {
            $timeline[] = [
                'action' => 'SOA Docs Uploaded',
                'date' => $this->soa_docs_uploaded_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->soa_docs_uploaded_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $this->soa_uploaded_by ?: 'Developer',
            ];
        }

        if ($this->soa_sent_to_buyer_at) {
            $timeline[] = [
                'action' => 'SOA Sent to Buyer',
                'date' => $this->soa_sent_to_buyer_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->soa_sent_to_buyer_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $this->creator ? $this->creator->full_name : 'System',
            ];
        }

        return $timeline;
    }

    /**
     * Delete attachment file when deleting the model
     */
    protected static function booted()
    {
        static::deleting(function ($pop) {
            if ($pop->attachment_path && Storage::disk('public')->exists($pop->attachment_path)) {
                Storage::disk('public')->delete($pop->attachment_path);
            }
            if ($pop->receipt_path && Storage::disk('public')->exists($pop->receipt_path)) {
                Storage::disk('public')->delete($pop->receipt_path);
            }
            if ($pop->soa_docs_path && Storage::disk('public')->exists($pop->soa_docs_path)) {
                Storage::disk('public')->delete($pop->soa_docs_path);
            }
        });
    }
}
