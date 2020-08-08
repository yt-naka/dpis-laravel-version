<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use \App\Product; //add
use \App\History; //add
use \App\HistoryDetail; //add
use Carbon\Carbon; //add

class ChangeRateController extends Controller
{
    public function index()
    {
        /*
        $products = Product::get();
        foreach($products as $product){
            $yafuoku_oldest_history_average_price = History::where('product_id', $product->id)->where('flea_market_name', 'yafuoku')->first()->average_price;
            $yafuoku_latest_history_average_price = History::where('product_id', $product->id)
                ->where('flea_market_name', 'yafuoku')->where('sample_num', 10)->orderBy('created_at', 'desc')->first()->average_price;
            $rakuma_oldest_history_average_price = History::where('product_id', $product->id)->where('flea_market_name', 'rakuma')->first()->average_price;
            $rakuma_latest_history_average_price = History::where('product_id', $product->id)
                ->where('flea_market_name', 'rakuma')->where('sample_num', 10)->orderBy('created_at', 'desc')->first()->average_price;
            if($yafuoku_oldest_history_average_price !== 0){
                $product['yafuoku_change_rate'] = ($yafuoku_latest_history_average_price - $yafuoku_oldest_history_average_price) / $yafuoku_oldest_history_average_price;
            }
            if($rakuma_oldest_history_average_price !== 0){
                $product['rakuma_change_rate'] = ($rakuma_latest_history_average_price - $rakuma_oldest_history_average_price) / $rakuma_oldest_history_average_price;
            }
        }
        dd($products->orderBy('yafuoku_change_rate', 'desc'));
        */
        return view('products_change_rate')->with(['a' => 2]);
    }
}
