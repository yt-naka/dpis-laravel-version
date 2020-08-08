<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use \App\Product; //add
use \App\History; //add
use \App\HistoryDetail; //add
use Carbon\Carbon; //add

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('name', 'like', '%'.$request->keyword.'%')
            ->orWhere('product_id', 'like', '%'.$request->keyword.'%')->get();
        foreach ($products as $product) {
            $product->product_id_for_img
                = str_replace('+', '', $product->product_id);
        }
        return view('search')->with(
            [
                "products" => $products,
                "now_ymd" => Carbon::now('Asia/Tokyo')->toDateString()
            ]
        );
    }
}
