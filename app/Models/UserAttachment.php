<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'unit_id',
        'filename',
        'type',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'unit_id' => 'integer',
    ];

    protected $appends = ['full_url'];

    protected $with = ['unit.property'];

    /**
     * Get the user that owns the attachment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the unit associated with this attachment.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the full URL for the file.
     */
    /**
     * Get the full URL for the file.
     */
    public function getFullUrlAttribute(): string
    {
        // For unit attachments, compute path from unit relationship
        if ($this->unit_id && $this->unit) {
            $folderPath = 'attachments/' . $this->unit->property->project_name . '/' . $this->unit->unit;
            return url('storage/' . $folderPath . '/' . $this->filename);
        }
        
        // For user attachments (legacy), compute path from user_id
        if ($this->user_id) {
            return url('/api/users/' . $this->user_id . '/attachments/' . $this->id);
        }
        
        return '';
    }
}
