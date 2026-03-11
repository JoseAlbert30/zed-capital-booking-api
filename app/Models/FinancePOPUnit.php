<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FinancePOPUnit extends Model
{
    protected $table = 'finance_pop_units';

    protected $fillable = [
        'pop_id',
        'unit_id',
        'unit_number',
        'receipt_path',
        'receipt_name',
        'receipt_uploaded_at',
        'receipt_uploaded_by',
    ];

    protected $casts = [
        'receipt_uploaded_at' => 'datetime',
    ];

    protected $appends = ['receipt_url'];

    public function pop()
    {
        return $this->belongsTo(FinancePOP::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getReceiptUrlAttribute()
    {
        return $this->receipt_path;
    }
}
