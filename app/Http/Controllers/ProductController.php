<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use \App\Product; //add
use \App\History; //add
use \App\HistoryDetail; //add
use Carbon\Carbon; //add

class ProductController extends Controller
{
    public function index(Request $request)
    {
        require app_path('Php/vardata.php');
        require app_path('Php/function.php');

        $product = Product::where('product_id', $request->product_id)->first();
        
        $histories = array();
        $history_details = array();
        $average_price = array();

        $times = get_time_array();
        $average_prices = array();
        $histories_created_at_array = array();
        $regression_line_array = array();

        $average_price_calculation_result = array();

        foreach ($FLEMA_LIST as $flema) {
            $histories[$flema] = History::where('product_id', $product->id)
                ->where('flea_market_name', $flema)
                ->where(function ($query) {
                    $query->where('sample_num', 0)->orWhere('sample_num', 10);
                })->oldest()->get();

            foreach ($histories[$flema] as $history) {
                if ($history->start_detail_id === 0
                    || $history->end_detail_id === 0) {
                    continue;
                }
                $aaa = HistoryDetail::where('product_id', $product->id)
                        ->where('flema', $flema)
                        ->whereBetween('id', [$history->start_detail_id, $history->end_detail_id])
                        ->where('status', 1)->get();
                //$aaa = array_reverse($aaa);
                $average_price_calculation_result[$flema]
                [$history->created_at->format('m/d')]
                    = $aaa->where('status', 1)->avg('price');
            }

            $history_details[$flema]
                = HistoryDetail::where('product_id', $product->id)
                               ->where('flema', $flema)
                               ->where('status', 1)->get()->toArray();
            $reverse = $history_details[$fle]

            $average_price[$flema] = round(
                $reverse
            );



            $histories_created_at_array[$flema]
                = Get_Created_At_array($histories[$flema]);

            $average_prices[$flema] = Get_Vertical_Line_array(
                $times,
                $histories_created_at_array[$flema],
                $histories[$flema]
            ); /* my function */
            
            $regression_line_array[$flema]
                = Get_Regression_line($times, $average_prices[$flema]);
        }
        
        $KEYWORDS = $product->name.'+'.$product->product_id.'+ドラゴンボールヒーローズ';

        return view('product')->with([
            'history_details' => $history_details,
            'keywords' => $KEYWORDS, 'times' => $times,
            'regression_line_array' => $regression_line_array,
            'average_price' => $average_price,
            'product_id' => $product->id,
            'average_prices' => $average_prices,
            'average_price_calculation_result'
                => $average_price_calculation_result
        ]);
    }
}
