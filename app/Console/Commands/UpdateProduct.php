<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use \App\Product; // add
use \App\History; // add
use Carbon\Carbon; // add
use \App\HistoryDetail; // add
use \App\HistoryDetailExecutionHistory; // add
use Illuminate\Support\Facades\Log; // add

class UpdateProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        require app_path('Php/vardata.php');
        require app_path('Php/function.php');

        $times = get_time_array();
        $NOW_YMD = Carbon::now('Asia/Tokyo')->toDateString();

        $products = Product::get();
        foreach ($products as $product) {
            $doesnt_exist_yafuoku_history
                = History::where('product_id', $product->id)
                    ->where('sample_num', 10)->where('is_history_on', $NOW_YMD)
                    ->where('flea_market_name', 'yafuoku')->doesntExist();
            $doesnt_exist_rakuma_history
                = History::where('product_id', $product->id)
                    ->where('sample_num', 10)->where('is_history_on', $NOW_YMD)
                    ->where('flea_market_name', 'rakuma')->doesntExist();
            if ($doesnt_exist_rakuma_history || $doesnt_exist_yafuoku_history) {
                Log::error('error');
                exit;
            }
        }

        
        foreach ($products as $product) {
            $yafuoku_history_price = History::where('product_id', $product->id)
                ->where('sample_num', 10)->where('is_history_on', $NOW_YMD)
                ->where('flea_market_name', 'yafuoku')
                ->first()->average_price;
            $rakuma_history_price = History::where('product_id', $product->id)
                ->where('sample_num', 10)->where('is_history_on', $NOW_YMD)
                ->where('flea_market_name', 'rakuma')
                ->first()->average_price;
            $average_price = ($yafuoku_history_price + $rakuma_history_price) / 2;

            $histories = array();
            $histories_created_at_array = array();
            $vertical_line_array = array();
            $regression_line_array = array();
            $change_rate_list = array();
            foreach ($FLEMA_LIST as $flema) {
                $histories[$flema] = History::where('product_id', $product->id)
                ->where('flea_market_name', $flema)
                ->where(function ($query) {
                    $query->where('sample_num', 0)->orWhere('sample_num', 10);
                })->oldest('is_history_on')->get();
                $histories_created_at_array[$flema]
                    = Get_Created_At_array($histories[$flema]);
                $vertical_line_array[$flema] = Get_Vertical_Line_array(
                    $times,
                    $histories_created_at_array[$flema],
                    $histories[$flema]
                );
                $regression_line_array[$flema]
                    = Get_Regression_line($times, $vertical_line_array[$flema]);
                $regression_line_start_price = 0;
                foreach ($regression_line_array[$flema] as $regression_line_one) {
                    if (!is_null($regression_line_one)) {
                        $regression_line_start_price = $regression_line_one;
                        break;
                    }
                }
                $regression_line_end_price = 0;
                foreach ($regression_line_array[$flema] as $regression_line_one) {
                    if (!is_null($regression_line_one)) {
                        $regression_line_end_price = $regression_line_one;
                    }
                }
                if ((int)$regression_line_start_price !== 0) {
                    $change_rate_list[$flema]
                        = ($regression_line_end_price - $regression_line_start_price)
                        / $regression_line_start_price;
                } else {
                    $change_rate_list[$flema] = 0;
                }
            }
            $change_rate = ($change_rate_list['yafuoku'] + $change_rate_list['rakuma']) / 2;

            /***************************************/
            $product->fill([
                'average_price' => $average_price,
                'change_rate' => $change_rate
            ])->save();
            /***************************************/
        }
    }
}
