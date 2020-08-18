<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use \App\Product; // add
use \App\History; // add
use Carbon\Carbon; // add
use \App\HistoryDetail; // add
use Illuminate\Support\Facades\Log; // add

class InspectHistoryTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'history:inspect';

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

        $NOW_YMD = Carbon::now('Asia/Tokyo')->toDateString();

        Log::info('start inspect-history-table');

        $DATE_ARRAY = [
            '2020-08-10', '2020-08-11', '2020-08-12', '2020-08-13',
            '2020-08-14', '2020-08-15'
        ];

        // ある日のあるproduct-idのあるフリマのあるサンプル数のhistoryが一個あるか確認
        foreach ($DATE_ARRAY as $date) {
            $date_histories = History::where('is_history_on', $date)->get();
            ///$time_start = microtime(true);
            for ($product_id = 1; $product_id <= Product::count(); $product_id++) {
                $date_product_histories = $date_histories->where('product_id', $product_id);
                foreach ($FLEMA_LIST as $flema) {
                    $date_product_flema_histories = $date_product_histories->where('flea_market_name', $flema);
                    foreach ($SAMPLE_NUM_LIST as $sample_num) {
                        $count = $date_product_flema_histories->where('sample_num', $sample_num)->count();
                        
                        if ($count === 0) {
                            Log::error(
                                "This history doesn't exist (date: {$date}, "
                                ."product-id: {$product_id}, "
                                ."flema: {$flema}, sample_num:{$sample_num})"
                            );
                        }
                        
                        if ($count >= 2) {
                            Log::error(
                                "Two or more history exist (date: {$date}, "
                                ."product-id: {$product_id}, "
                                ."flema: {$flema}, sample_num:{$sample_num})"
                            );
                        }
                    }
                }
            }
            //$time = microtime(true) - $time_start;
            //dd($time);
        }
        Log::info(
            'end inspecting whether all any-date, any-product-id, any-flema, '
            .'any-sanple-num history num is one'
        );
        
        // あるサンプル数でのaverarge-priceが本当にあるサンプル数で計算しているか確認
        // and 計算結果が間違っていないか確認
        foreach ($DATE_ARRAY as $date) {
            $histories = History::where('is_history_on', $date)->get();
            foreach ($histories as $history) {
                $calc_history_avg_price = (int)round($history->historyDetails()->avg('price'));
                $actual_history_detail_sample_num = $history->historyDetails()->count();
                if ($actual_history_detail_sample_num > $history->sample_num) {
                    Log::error(
                        "sample-num error, forecast: {$history->sample_num} or less, "
                        ."actual: {$actual_history_detail_sample_num} "
                        ."history-id: {$history->id}, is-history-on: {$history->is_history_on} "
                        ."product-id: {$history->product_id}"
                    );
                }
                if ($calc_history_avg_price !== $history->average_price) {
                    Log::error(
                        "average-price error, history-id: {$history->id}, "
                        ."forecast: {$history->average_price}, actual: {$calc_history_avg_price} "
                        ."product-id: {$history->product_id}"
                    );
                }
            }
        }
    }
}
