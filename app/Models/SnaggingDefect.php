<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SnaggingDefect extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'image_path',
        'description',
        'location',
        'agreed_remediation_action',
        'is_remediated',
        'created_by',
    ];

    protected $casts = [
        'is_remediated' => 'boolean',
    ];

    /**
     * Append attributes to the model's array form.
     */
    protected $appends = ['image_url'];

    /**
     * Get the booking that owns the defect.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who created the defect.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the full URL for the defect image.
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }
        return url('api/storage/' . $this->image_path);
    }
}
