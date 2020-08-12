<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use \App\Product; // add
use \App\History; // add
use Carbon\Carbon; // add
use \App\HistoryDetail; // add
use \App\HistoryDetailExecutionHistory; // add
use Illuminate\Support\Facades\Log; // add

class CreateHistoryUsingHistoryDetail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'history:create';

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
        // 前提 history_detailsテーブルが全て正しい

        
        
        require app_path('Php/vardata.php');
        require app_path('Php/function.php');

        $IS_HISTORY_ON = Carbon::now('Asia/Tokyo')->toDateString(); // 任意
        $products = Product::get();

        foreach ($products as $product) {
            foreach ($FLEMA_LIST as $flema_name) {
                if (HistoryDetailExecutionHistory::where('product_id', $product->id)
                    ->where('flema', $flema_name)->where('is_history_on', $IS_HISTORY_ON)->doesntExist()) {
                    Log::error("can't start creating history using history-detail");
                    exit;
                }
            }
        }

        Log::info('start creating history using history-detail');

        foreach ($products as $product) {
            foreach ($FLEMA_LIST as $flema_name) {
                $doesnt_exist_sample_num = array();
                foreach ($SAMPLE_NUM_LIST as $sample_num) {
                    $doesnt_exist_sample_num["{$sample_num}"]
                        = History::where('product_id', $product->id)
                            ->where('flea_market_name', $flema_name)
                            ->where('is_history_on', $IS_HISTORY_ON)
                            ->where('sample_num', $sample_num)->doesntExist();
                }

                // history_detailsテーブルを用いて平均価格を計算
                $average_prices = array();
                $start_history_detail_id = array('1' => 0, '5' => 0, '10' => 0);
                $end_history_detail_id = array('1' => 0, '5' => 0, '10' => 0);

                $success_history_details = HistoryDetail::where('product_id', $product->id)
                                            ->where('flema', $flema_name)
                                            ->where('status', 1)->get(); // 古 → 新
                $reverse_success_history_details
                    = array_reverse($success_history_details->toArray()); // 新 → 古
                $sample_num_array = array('1' => array(), '5' => array(), '10' => array());

                foreach ($reverse_success_history_details as $success_history_detail) {
                    foreach ($SAMPLE_NUM_LIST as $sample_num) {
                        if (count($sample_num_array["{$sample_num}"]) >= $sample_num) {
                            continue;
                        }
                        $sample_num_array["{$sample_num}"][] = $success_history_detail['price'];

                        if ($end_history_detail_id["{$sample_num}"] === 0) {
                            $end_history_detail_id["{$sample_num}"] = $success_history_detail['id'];
                        }
                        $start_history_detail_id["{$sample_num}"] = $success_history_detail['id'];
                    }
                }

                foreach ($SAMPLE_NUM_LIST as $sample_num) {
                    if (count($sample_num_array["{$sample_num}"]) === 0) {
                        $average_prices["sample_num_{$sample_num}"] = 0;
                        continue;
                    }
                    $average_prices["sample_num_{$sample_num}"]
                        = array_sum($sample_num_array["{$sample_num}"])
                            / count($sample_num_array["{$sample_num}"]);
                }
                //dd($flema_data["average_prices"]);

                // histories
                foreach ($SAMPLE_NUM_LIST as $sample_num) {
                    if (!$doesnt_exist_sample_num["{$sample_num}"]) {
                        continue;
                    };
                    History::forceCreate([
                        'product_id' => $product->id,
                        'average_price' => $average_prices["sample_num_{$sample_num}"],
                        'flea_market_name' => $flema_name,
                        'sample_num' => $sample_num,
                        'start_detail_id' => $start_history_detail_id["{$sample_num}"],
                        'end_detail_id' => $end_history_detail_id["{$sample_num}"],
                        'is_history_on' => $IS_HISTORY_ON
                    ]);
                }
                // products
                if ($flema_name === 'yafuoku') {
                    // only yafuoku price ;
                    $product->fill([
                        'average_price' => $average_prices['sample_num_10'],
                    ])->save();
                }
            }
        }

        Log::info("completed creating history-details on {$IS_HISTORY_ON}");
    }
}
