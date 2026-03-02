<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FinanceThirdparty extends Model
{
    protected $fillable = [
        'thirdparty_number',
        'project_name',
        'unit_id',
        'unit_number',
        'thirdparty_name',
        'description',
        'notes',
        'form_document_path',
        'form_document_name',
        'form_uploaded_at',
        'sent_to_buyer',
        'sent_to_buyer_at',
        'sent_to_buyer_email',
        'signed_document_path',
        'signed_document_name',
        'signed_document_uploaded_at',
        'proof_of_payment_path',
        'proof_of_payment_name',
        'proof_of_payment_uploaded_at',
        'sent_to_developer',
        'sent_to_developer_at',
        'viewed_by_developer',
        'viewed_at',
        'receipt_document_path',
        'receipt_document_name',
        'receipt_uploaded_at',
        'receipt_uploaded_by',
        'receipt_sent_to_buyer',
        'receipt_sent_to_buyer_at',
        'created_by',
    ];

    protected $casts = [
        'form_uploaded_at' => 'datetime',
        'sent_to_buyer' => 'boolean',
        'sent_to_buyer_at' => 'datetime',
        'signed_document_uploaded_at' => 'datetime',
        'proof_of_payment_uploaded_at' => 'datetime',
        'sent_to_developer' => 'boolean',
        'sent_to_developer_at' => 'datetime',
        'viewed_by_developer' => 'boolean',
        'viewed_at' => 'datetime',
        'receipt_uploaded_at' => 'datetime',
        'receipt_sent_to_buyer' => 'boolean',
        'receipt_sent_to_buyer_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['form_document_url', 'signed_document_url', 'proof_of_payment_url', 'receipt_document_url', 'timeline'];

    /**
     * Get the user who created this thirdparty
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the unit associated with this thirdparty
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the attachments for this thirdparty
     */
    public function attachments()
    {
        return $this->morphMany(FinanceAttachment::class, 'attachable');
    }

    /**
     * Get the form document URL
     */
    public function getFormDocumentUrlAttribute()
    {
        return $this->form_document_path ? '/storage/' . $this->form_document_path : null;
    }

    /**
     * Get the signed document URL
     */
    public function getSignedDocumentUrlAttribute()
    {
        return $this->signed_document_path ? '/storage/' . $this->signed_document_path : null;
    }

    /**
     * Get the proof of payment URL
     */
    public function getProofOfPaymentUrlAttribute()
    {
        return $this->proof_of_payment_path ? '/storage/' . $this->proof_of_payment_path : null;
    }

    /**
     * Get the receipt document URL
     */
    public function getReceiptDocumentUrlAttribute()
    {
        return $this->receipt_document_path ? '/storage/' . $this->receipt_document_path : null;
    }

    /**
     * Get the timeline of events
     */
    public function getTimelineAttribute()
    {
        $timeline = [];

        if ($this->created_at) {
            $timeline[] = [
                'action' => 'Thirdparty Form Created',
                'date' => $this->created_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->created_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $this->creator ? $this->creator->full_name : 'Admin',
            ];
        }

        if ($this->sent_to_buyer_at) {
            $timeline[] = [
                'action' => 'Sent to Buyer',
                'date' => $this->sent_to_buyer_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->sent_to_buyer_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $this->creator ? $this->creator->full_name : 'Admin',
            ];
        }

        if ($this->signed_document_uploaded_at) {
            $timeline[] = [
                'action' => 'Signed Document Uploaded',
                'date' => $this->signed_document_uploaded_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->signed_document_uploaded_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => 'Admin',
            ];
        }

        if ($this->proof_of_payment_uploaded_at) {
            $timeline[] = [
                'action' => 'Proof of Payment Uploaded',
                'date' => $this->proof_of_payment_uploaded_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->proof_of_payment_uploaded_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => 'Admin',
            ];
        }

        if ($this->sent_to_developer_at) {
            $timeline[] = [
                'action' => 'Sent to Developer',
                'date' => $this->sent_to_developer_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->sent_to_developer_at->timezone('Asia/Dubai')->format('H:i:s'),
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

        if ($this->receipt_uploaded_at) {
            $timeline[] = [
                'action' => 'Receipt Uploaded',
                'date' => $this->receipt_uploaded_at->timezone('Asia/Dubai')->format('Y-m-d'),
                'time' => $this->receipt_uploaded_at->timezone('Asia/Dubai')->format('H:i:s'),
                'user' => $this->receipt_uploaded_by ?: 'Developer',
            ];
        }

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
     * Delete document files when deleting the model
     */
    protected static function booted()
    {
        static::deleting(function ($thirdparty) {
            if ($thirdparty->form_document_path && Storage::disk('public')->exists($thirdparty->form_document_path)) {
                Storage::disk('public')->delete($thirdparty->form_document_path);
            }
            if ($thirdparty->signed_document_path && Storage::disk('public')->exists($thirdparty->signed_document_path)) {
                Storage::disk('public')->delete($thirdparty->signed_document_path);
            }
            if ($thirdparty->proof_of_payment_path && Storage::disk('public')->exists($thirdparty->proof_of_payment_path)) {
                Storage::disk('public')->delete($thirdparty->proof_of_payment_path);
            }
            if ($thirdparty->receipt_document_path && Storage::disk('public')->exists($thirdparty->receipt_document_path)) {
                Storage::disk('public')->delete($thirdparty->receipt_document_path);
            }
        });
    }
}
