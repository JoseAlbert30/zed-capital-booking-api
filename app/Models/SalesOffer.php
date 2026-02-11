<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOffer extends Model
{
    protected $fillable = [
        'project_name',
        'unit_no',
        'bedrooms',
        'sqft',
        'price_5050',
        'dld_5050',
        'price_3070',
        'dld_3070',
        'admin_fee',
    ];
}
