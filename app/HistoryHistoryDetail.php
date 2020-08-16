<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistoryHistoryDetail extends Model
{
    protected $fillable = [
        'history_id', 'history_detail_id'
    ];
    protected $table = 'history_history_detail';
}
