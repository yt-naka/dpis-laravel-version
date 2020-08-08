<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $guarded = [
        'product_id', 'average_price', 'flea_market_name', 'sample_num'
    ];
}