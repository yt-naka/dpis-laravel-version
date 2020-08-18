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
        
        Log::info("Attempting to start create-history-using-history-detail on {$IS_HISTORY_ON} ...");

        $products = Product::get();
        foreach ($products as $product) {
            foreach ($FLEMA_LIST as $flema_name) {
                if (HistoryDetailExecutionHistory::where('product_id', $product->id)
                    ->where('flema', $flema_name)->whereDate('created_at', $IS_HISTORY_ON)
                    ->doesntExist()) {
                    Log::error("Can't start because you haven't scraped all products yet");
                    exit;
                }
            }
        }

        foreach ($products as $product) {
            foreach ($FLEMA_LIST as $flema_name) {
                foreach ($SAMPLE_NUM_LIST as $sample_num) {
                    if (History::where('product_id', $product->id)
                        ->where('flea_market_name', $flema_name)
                        ->where('is_history_on', $IS_HISTORY_ON)
                        ->where('sample_num', $sample_num)->doesntExist()) {
                        // history_detail_execution_historyが全て実行され、
                        // どれか一つでもhistoryがない場合、メイン処理実行
                        goto main;
                    }
                }
            }
        }

        Log::warning("Can't start because all histories already created");
        exit;

        main:
        Log::info('Start');

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

                if (!$doesnt_exist_sample_num['1'] && !$doesnt_exist_sample_num['5']
                    && !$doesnt_exist_sample_num['10']) {
                        continue;
                }

                // ここに来る時点で全商品既にスクレイピングしてるはずだけど一応
                if (HistoryDetailExecutionHistory::where('product_id', $product->id)
                    ->where('flema', $flema_name)->whereDate('created_at', $IS_HISTORY_ON)
                    ->doesntExist()) {
                    continue;
                }

                /* oldest_to_latest_numberが重複していないかチェック */
                $each_count = array_count_values(
                    HistoryDetail::where('product_id', $product->id)
                    ->where('flema', $flema_name)
                    ->where('oldest_to_latest_number', '!=', 0)
                    ->pluck('oldest_to_latest_number')
                    ->toArray()
                );
                if (count($each_count) > 0 && max($each_count) > 1) {
                    Log::error(
                        "oldest-to-latest-number error , product-id: {$product->id} "
                        ."flema: '{$flema_name}'"
                    );
                    exit;
                }

                // history_detailsテーブルを用いて平均価格を計算
                $average_prices = array();
                $oldest_to_latest_success_history_details
                    = HistoryDetail::where('product_id', $product->id)
                    ->where('flema', $flema_name)
                    ->where('status', 1)
                    ->orderBy('oldest_to_latest_number', 'asc')
                    ->get();
                $latest_to_oldest_success_history_details
                    = array_reverse($oldest_to_latest_success_history_details->toArray()); // 新 → 古
                $sample_product_price_list = array('1' => array(), '5' => array(), '10' => array());
                $sample_product_id_list = array('1' => array(), '5' => array(), '10' => array());

                foreach ($latest_to_oldest_success_history_details as $success_history_detail) {
                    foreach ($SAMPLE_NUM_LIST as $sample_num) {
                        if (count($sample_product_price_list["{$sample_num}"]) >= $sample_num) {
                            continue;
                        }
                        
                        $sample_product_price_list["{$sample_num}"][] = $success_history_detail['price'];
                        $sample_product_id_list["{$sample_num}"][] = $success_history_detail['id'];
                    }
                }

                foreach ($SAMPLE_NUM_LIST as $sample_num) {
                    if (count($sample_product_price_list["{$sample_num}"]) === 0) {
                        $average_prices["sample_num_{$sample_num}"] = 0;
                    } else {
                        $average_prices["sample_num_{$sample_num}"]
                            = array_sum($sample_product_price_list["{$sample_num}"])
                                / count($sample_product_price_list["{$sample_num}"]);
                    }
                }

                foreach ($SAMPLE_NUM_LIST as $sample_num) {
                    if (!$doesnt_exist_sample_num["{$sample_num}"]) {
                        continue;
                    };
                    /******************************************************************************/
                    $history = History::forceCreate([
                        'product_id' => $product->id,
                        'average_price' => $average_prices["sample_num_{$sample_num}"],
                        'flea_market_name' => $flema_name,
                        'sample_num' => $sample_num,
                        'start_detail_id' => 0,
                        'end_detail_id' => 0,
                        'is_history_on' => $IS_HISTORY_ON
                    ]);
                    $history->historyDetails()->attach($sample_product_id_list["{$sample_num}"]);
                    /******************************************************************************/
                }
            }
        }

        Log::info("Completed create-history-using-history-detail on {$IS_HISTORY_ON}");
    }
}
