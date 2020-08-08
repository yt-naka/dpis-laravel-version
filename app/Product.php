<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'average_price', 'change_rate'
    ];

    protected $guarded = [
        'product_id', 'name', 'rarity', 'mission', 'model'
    ];
}
