<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistoryDetailExecutionHistory extends Model
{
    protected $guarded = [
        'product_id', 'flema'
    ];
}
