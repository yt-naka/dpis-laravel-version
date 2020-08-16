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

    public function histories()
    {
        return $this->hasMany('App\History');
    }
    public function historyDetails()
    {
        return $this->hasMany('App\HistoryDetail');
    }
    public function historyDetailExecutionHistories()
    {
        return $this->hasMany('App\HistoryDetailExecutionHistory');
    }
}
