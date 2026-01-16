<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitRemark extends Model
{
    protected $fillable = [
        'unit_id',
        'date',
        'time',
        'event',
        'type',
        'admin_name',
    ];

    /**
     * Get the unit that owns the remark.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
