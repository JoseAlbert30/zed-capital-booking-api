<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'mobile_number',
        'payment_status',
        'payment_date',
        'handover_ready',
        'has_mortgage',
        'handover_email_sent',
        'handover_email_sent_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'payment_date' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get all bookings for the user.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get all units owned by the user (including joint ownership).
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get units where user is the primary buyer.
     */
    public function primaryUnits(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_user')
            ->wherePivot('is_primary', true)
            ->withTimestamps();
    }

    /**
     * Get units where user is a co-buyer.
     */
    public function coBuyerUnits(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_user')
            ->wherePivot('is_primary', false)
            ->withTimestamps();
    }

    /**
     * Get all attachments for the user.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(UserAttachment::class);
    }

    /**
     * Get the remarks for the user.
     */
    public function remarks(): HasMany
    {
        return $this->hasMany(Remark::class)->orderBy('date', 'desc')->orderBy('time', 'desc');
    }
}
