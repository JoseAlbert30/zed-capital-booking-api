<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_name',
        'location',
        'developer_email',
        'developer_name',
        'cc_emails',
        'penalty_initiated_by',
        'handover_checklist_template',
        'declaration_template',
    ];

    /**
     * Get the units for the property.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Get claimed units.
     */
    public function claimedUnits(): HasMany
    {
        return $this->hasMany(Unit::class)->where('status', 'claimed');
    }

    /**
     * Get unclaimed units.
     */
    public function unclaimedUnits(): HasMany
    {
        return $this->hasMany(Unit::class)->where('status', 'unclaimed');
    }
}
