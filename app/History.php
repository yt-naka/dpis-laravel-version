<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $fillable = [
        'average_price', 'start_detail_id', 'end_detail_id'
    ];
    protected $guarded = [
        'product_id', 'flea_market_name', 'sample_num', 'is_history_on'
    ];
    protected $dates = ['is_history_on'];
}