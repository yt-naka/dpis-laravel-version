<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use \App\Product; //add
use Carbon\Carbon; //add
use \App\History; //add

class ProductsController extends Controller
{
    public function index(Request $request)
    {

        require app_path('Php/vardata.php');
        require app_path('Php/function.php');


        if (!is_null($request->desc)) {
            $ORDER = $request->desc;
            $products = Product::orderBy($request->desc, 'desc')->get();
        } elseif (!is_null($request->asc)) {
            $ORDER = $request->asc;
            $products = Product::orderBy($request->asc, 'asc')->get();
        } elseif (!is_null($request->oldest_to_latest)
                  || !is_null($request->latest_to_oldest)) {
            $ORDER = 'average_price';
            $OLDEST_TO_LATEST_MISSIONS = ['H', 'HG', 'HJ', 'HGD', 'SH', 'UM', 'BM'];
            $products = array();
            foreach ($OLDEST_TO_LATEST_MISSIONS as $mission) {
                $mission_cards = Product::where('mission', $mission)->get();
                foreach ($mission_cards as $card) {
                    $products[] = $card;
                }
            }
            if (!is_null($request->latest_to_oldest)) {
                $products = array_reverse($products);
            }
        } else {
            $ORDER = 'average_price';
            $products = Product::orderBy('average_price', 'desc')->get();
        }

        $horizontal_line_card_number = array();
        $vertical_line_average_price = array();
        $URL = 'app/public/all_cards_infos/rarity4cp_cards.json';
        $CP_CARDS_JSON = file_get_contents(storage_path($URL));
        $CP_CARDS = json_decode($CP_CARDS_JSON, true);

        $horizontal_line_array = get_time_array(); /* my function */

        foreach ($products as $product) {
            $product->product_id_for_img
                = str_replace('PR', '', str_replace('+', '', $product->product_id));

            if (!is_null($request->oldest_to_latest)
                || !is_null($request->latest_to_oldest)
                || count($horizontal_line_card_number) < 50) {
                $horizontal_line_card_number[] = $product->product_id;
                $vertical_line_anything[] = $product[$ORDER];
            }


            if (count($horizontal_line_card_number) < 10) { // all_products_price_trends用
                $histories = array();
                $histories_created_at_array = array();
                $product_prices = array();

                $histories['yafuoku'] = History::where('product_id', $product->id)
                    ->where('flea_market_name', 'yafuoku')->where(function ($query) {
                        $query->where('sample_num', 0)->orWhere('sample_num', 10);
                    })->oldest('is_history_on')->get();

                $histories_created_at_array['yafuoku'] = Get_Created_At_array(
                    $histories['yafuoku']
                );
                $product_prices['yafuoku'] = Get_Vertical_Line_array(
                    $horizontal_line_array,
                    $histories_created_at_array['yafuoku'],
                    $histories['yafuoku']
                );

                $vertical_line_average_price['yafuoku'][] = array(
                    "data" => $product_prices['yafuoku'], "label" => $product->product_id,
                    "backgroundColor" => "rgba(0,0,0,0)"
                );
            }
        }
        //dd($all_products_price_trends_data);
        return view('products')->with([
            'products' => $products,
            'horizontal_line_card_number' => $horizontal_line_card_number,
            'vertical_line_anything' => $vertical_line_anything,
            'average_price_sum' => Product::sum("average_price"),
            'product_num' => Product::count(),
            'horizontal_line_date' => $horizontal_line_array,
            'vertical_line_average_price' => $vertical_line_average_price,
            'now_ymd' => Carbon::now('Asia/Tokyo')->toDateString()
        ]);
    }
}
