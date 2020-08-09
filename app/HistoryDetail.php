<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistoryDetail extends Model
{
    protected $fillable = [
        'status'
    ];

    protected $guarded = [
        'product_id', 'flema', 'price', 'title', 'url', 'img_url', 'end_date_time'
    ];
}
