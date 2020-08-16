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
        $success_history_details = array();
        $reverse_success_history_details = array();
        $history_details = array();
        $reverse_history_details = array();
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
                })->oldest('is_history_on')->get();


            $history_details[$flema] = HistoryDetail::where('product_id', $product->id)
                                        ->where('flema', $flema)
                                        ->orderBy('oldest_to_latest_number', 'asc')
                                        ->get();
            $reverse_history_details[$flema] = array_reverse($history_details[$flema]->toArray());

            // history_detailsテーブルを基に平均価格を計算
            $success_history_details[$flema]
                = HistoryDetail::where('product_id', $product->id)
                               ->where('flema', $flema)
                               ->where('status', 1)->get();
            $sample_num_array = array('1' => array(), '5' => array(), '10' => array());
            
            $start_history_detail_id = array(1 => 0, 5 => 0, 10 => 0);
            $end_history_detail_id = array(1 => 0, 5 => 0, 10 => 0);

            $reverse_success_history_details[$flema]
                = array_reverse($success_history_details[$flema]->toArray());
            foreach ($reverse_success_history_details[$flema] as $success_history_detail) {
                foreach ($SAMPLE_NUM_LIST as $sample_num) {
                    if (count($sample_num_array[$sample_num]) >= $sample_num) {
                        continue;
                    }
                    $sample_num_array[$sample_num][] = $success_history_detail['price'];

                    if ($end_history_detail_id[$sample_num] === 0) {
                        $end_history_detail_id[$sample_num] = $success_history_detail['id'];
                    }
                    $start_history_detail_id[$sample_num] = $success_history_detail['id'];
                }
            }

            //dd($sample_num_array);
            foreach ($SAMPLE_NUM_LIST as $sample_num) {
                if (count($sample_num_array[$sample_num]) === 0) {
                    $average_prices[$flema]["sample_num_{$sample_num}"] = 0;
                    continue;
                }
                $average_prices[$flema]["sample_num_{$sample_num}"]
                    = array_sum($sample_num_array[$sample_num])
                        / count($sample_num_array[$sample_num]);
            }
            //dd($end_history_detail_id);
            // ここまで
            

            $histories_created_at_array[$flema]
                = Get_Created_At_array($histories[$flema]);

            
            $vertical_line_array[$flema] = Get_Vertical_Line_array(
                $times,
                $histories_created_at_array[$flema],
                $histories[$flema]
            ); /* my function */
            
            $regression_line_array[$flema]
                = Get_Regression_line($times, $vertical_line_array[$flema]);
            //dd($regression_line_array[$flema]);
        }
        
        $KEYWORDS = $product->name.'+'.$product->product_id.'+ドラゴンボールヒーローズ';

        return view('product')->with([
            'history_details' => $reverse_history_details,
            'keywords' => $KEYWORDS, 'times' => $times,
            'regression_line_array' => $regression_line_array,
            'product_id' => $product->id,
            'average_prices' => $average_prices,
            'vertical_line_array' => $vertical_line_array,
            'average_price_calculation_result'
                => $average_price_calculation_result
        ]);
    }
}
