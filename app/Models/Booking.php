<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'unit_id',
        'booked_date',
        'booked_time',
        'status',
        'handover_checklist',
        'handover_declaration',
        'handover_photo',
        'client_signature',
        'handover_completed_at',
        'handover_completed_by',
    ];

    protected $casts = [
        'booked_date' => 'datetime',
        'handover_completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'handover_checklist_url',
        'handover_declaration_url',
        'handover_photo_url',
        'client_signature_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the full URL for handover checklist.
     */
    public function getHandoverChecklistUrlAttribute(): ?string
    {
        if (!$this->handover_checklist) {
            return null;
        }
        return url('storage/' . $this->handover_checklist);
    }

    /**
     * Get the full URL for handover declaration.
     */
    public function getHandoverDeclarationUrlAttribute(): ?string
    {
        if (!$this->handover_declaration) {
            return null;
        }
        return url('storage/' . $this->handover_declaration);
    }

    /**
     * Get the full URL for handover photo.
     */
    public function getHandoverPhotoUrlAttribute(): ?string
    {
        if (!$this->handover_photo) {
            return null;
        }
        return url('storage/' . $this->handover_photo);
    }

    /**
     * Get the full URL for client signature.
     */
    public function getClientSignatureUrlAttribute(): ?string
    {
        if (!$this->client_signature) {
            return null;
        }
        return url('storage/' . $this->client_signature);
    }
}
