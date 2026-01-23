<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'unit',
        'floor',
        'building',
        'square_footage',
        'dewa_premise_number',
        'status',
        'payment_status',
        'payment_date',
        'has_mortgage',
        'handover_ready',
        'handover_status',
        'handover_email_sent',
        'handover_email_sent_at',
        'total_unit_price',
        'dld_fees',
        'admin_fee',
        'amount_to_pay',
        'total_amount_paid',
        'outstanding_amount',
    ];

    protected $casts = [
        'property_id' => 'integer',
        'payment_date' => 'date',
        'has_mortgage' => 'boolean',
        'handover_ready' => 'boolean',
        'handover_email_sent' => 'boolean',
        'handover_email_sent_at' => 'datetime',
    ];

    /**
     * Get the property that owns the unit.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the users (buyers) who own this unit.
     * Supports multiple buyers (joint ownership).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'unit_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the primary buyer for this unit.
     */
    public function primaryBuyer(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'unit_user')
            ->wherePivot('is_primary', true)
            ->withTimestamps();
    }

    /**
     * Get all co-buyers (non-primary) for this unit.
     */
    public function coBuyers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'unit_user')
            ->wherePivot('is_primary', false)
            ->withTimestamps();
    }

    /**
     * Get the attachments for this unit.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(UserAttachment::class, 'unit_id');
    }

    /**
     * Get the remarks for this unit.
     */
    public function remarks(): HasMany
    {
        return $this->hasMany(UnitRemark::class);
    }

    /**
     * Get the booking for this unit (singular - one booking per unit).
     */
    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    /**
     * Get the bookings for this unit.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
