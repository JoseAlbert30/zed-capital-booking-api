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

    // Remove automatic loading to prevent circular reference issues
    // protected $with = ['unit.property'];

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
    public function getFullUrlAttribute(): string
    {
        // Determine frontend URL based on environment
        $frontendUrl = config('app.frontend_url');
        
        // If not set in config, auto-detect based on environment
        if (!$frontendUrl || $frontendUrl === config('app.url')) {
            if (config('app.env') === 'production') {
                $frontendUrl = 'https://app.zedcapitalbooking.com';
            } else {
                $frontendUrl = 'http://localhost:3000';
            }
        }
        
        // For unit attachments, compute path from unit relationship
        if ($this->unit_id && $this->unit) {
            $folderPath = 'attachments/' . $this->unit->property->project_name . '/' . $this->unit->unit;
            return $frontendUrl . '/storage/' . $folderPath . '/' . $this->filename;
        }
        
        // For user attachments (legacy), use backend API URL
        if ($this->user_id) {
            $apiUrl = config('app.url');
            if (config('app.env') === 'production') {
                $apiUrl = str_replace('http://', 'https://', $apiUrl);
            }
            return $apiUrl . '/api/users/' . $this->user_id . '/attachments/' . $this->id;
        }
        
        return '';
    }
}
