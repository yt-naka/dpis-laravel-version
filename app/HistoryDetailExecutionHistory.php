<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistoryDetailExecutionHistory extends Model
{
    protected $guarded = [
        'product_id', 'flema'
    ];
    public function product()
    {
        return $this->belongTo('App\Product');
    }
}
