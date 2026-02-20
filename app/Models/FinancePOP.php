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
        'amount',
        'attachment_path',
        'attachment_name',
        'notification_sent',
        'notification_sent_at',
        'receipt_path',
        'receipt_name',
        'receipt_uploaded_at',
        'soa_requested',
        'soa_requested_at',
        'soa_docs_path',
        'soa_docs_name',
        'soa_docs_uploaded_at',
        'soa_sent_to_buyer',
        'soa_sent_to_buyer_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
        'receipt_uploaded_at' => 'datetime',
        'soa_requested' => 'boolean',
        'soa_requested_at' => 'datetime',
        'soa_docs_uploaded_at' => 'datetime',
        'soa_sent_to_buyer' => 'boolean',
        'soa_sent_to_buyer_at' => 'datetime',
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
        if ($this->attachment_path) {
            return Storage::url($this->attachment_path);
        }
        return null;
    }

    /**
     * Get the receipt URL.
     */
    public function getReceiptUrlAttribute()
    {
        return $this->receipt_path ? Storage::url($this->receipt_path) : null;
    }

    /**
     * Get the SOA docs URL.
     */
    public function getSoaDocsUrlAttribute()
    {
        return $this->soa_docs_path ? Storage::url($this->soa_docs_path) : null;
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
                'date' => $this->created_at->format('Y-m-d'),
                'time' => $this->created_at->format('H:i:s'),
                'user' => $this->creator ? $this->creator->full_name : 'System',
            ];
        }

        if ($this->notification_sent_at) {
            $timeline[] = [
                'action' => 'Sent to Developer',
                'date' => $this->notification_sent_at->format('Y-m-d'),
                'time' => $this->notification_sent_at->format('H:i:s'),
                'user' => $this->creator ? $this->creator->full_name : 'System',
            ];
        }

        if ($this->receipt_uploaded_at) {
            $timeline[] = [
                'action' => 'Receipt Uploaded',
                'date' => $this->receipt_uploaded_at->format('Y-m-d'),
                'time' => $this->receipt_uploaded_at->format('H:i:s'),
                'user' => 'Developer',
            ];
        }

        if ($this->soa_requested_at) {
            $timeline[] = [
                'action' => 'SOA Requested',
                'date' => $this->soa_requested_at->format('Y-m-d'),
                'time' => $this->soa_requested_at->format('H:i:s'),
                'user' => $this->creator ? $this->creator->full_name : 'System',
            ];
        }

        if ($this->soa_docs_uploaded_at) {
            $timeline[] = [
                'action' => 'SOA Docs Uploaded',
                'date' => $this->soa_docs_uploaded_at->format('Y-m-d'),
                'time' => $this->soa_docs_uploaded_at->format('H:i:s'),
                'user' => 'Developer',
            ];
        }

        if ($this->soa_sent_to_buyer_at) {
            $timeline[] = [
                'action' => 'SOA Sent to Buyer',
                'date' => $this->soa_sent_to_buyer_at->format('Y-m-d'),
                'time' => $this->soa_sent_to_buyer_at->format('H:i:s'),
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
