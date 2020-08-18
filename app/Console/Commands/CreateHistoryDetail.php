<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use \App\Product; // add
use \App\History; // add
use Carbon\Carbon; // add
use \App\HistoryDetail; // add
use \App\HistoryDetailExecutionHistory; // add
use Illuminate\Support\Facades\Log; // add

class UpdatePrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'history-detail:create';

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
        Log::info('Start update-price');
        
        require app_path('Php/vardata.php');
        require app_path('Php/function.php');

        $scraping_num = array(
            'yafuoku' => 0,
            'rakuma' => 0
        );

        $start_scraping_product_id = array(
            'yafuoku' => 0,
            'rakuma' => 0
        );

        $end_scraping_product_id = array(
            'yafuoku' => 0,
            'rakuma' => 0
        );

        $NOW_YMD = Carbon::now('Asia/Tokyo')->toDateString();
        $EXECUTABLE_FILE = app_path('Python/scrape.py');

        $products = Product::get();
        foreach ($products as $product) {


            /* main */
            /* data['yafuoku'] → data['rakuma'] */
            foreach ($FLEMA_LIST as $flema_name) {
                if (HistoryDetailExecutionHistory::where('product_id', $product->id)
                    ->where('flema', $flema_name)->whereDate('created_at', $NOW_YMD)->exists()) {
                    continue;
                }

                $arguments1 = "{$product->name}+{$product->product_id}+"
                            ."ドラゴンボールヒーローズ {$flema_name}";
                unset($outputs1);
                exec("{$PYTHON3_PATH} {$EXECUTABLE_FILE} {$arguments1}", $outputs1);
                $scraping_num[$flema_name]++;
                if ($start_scraping_product_id[$flema_name] === 0) {
                    $start_scraping_product_id[$flema_name] = $product->id;
                }
                $end_scraping_product_id[$flema_name] = $product->id;
                $flema_data = json_decode($outputs1[0], true);
                


                $all_history_details = HistoryDetail::where('product_id', $product->id)
                                        ->where('flema', $flema_name)->get();
                // スクレイピングできなかった過去の商品の配列を作成
                $past_products = array();
                $flema_product_url_list = array_column($flema_data['list'], 'url');
                foreach ($all_history_details as $history_detail) {
                    if (!in_array($history_detail->url, $flema_product_url_list)) {
                        $past_products[] = $history_detail;
                    }
                }
                // 現在スクレイピングできた商品のoldest-to-latest-numberは、
                // 少なくとも過去商品の最大値+1以上でなければならない
                if (count($past_products) > 0) {
                    $base_oldest_to_latest_number = max(array_column($past_products, 'oldest_to_latest_number')) + 1;
                    if ($base_oldest_to_latest_number === 0) {
                        $base_oldest_to_latest_number = 1;
                    }
                } else {
                    $base_oldest_to_latest_number = 1;
                }

                // DBにはあるけど、実際のページにはない商品を削除
                foreach ($all_history_details->where('oldest_to_latest_number', '>=', $base_oldest_to_latest_number) as $history) {
                    if (!in_array($history->url, $flema_product_url_list)) {
                        /*********************/
                        $history->delete();
                        /*********************/
                    }
                }

                $history_detail_url_list
                    = HistoryDetail::where('product_id', $product->id)
                    ->where('flema', $flema_name)->pluck('url')->toArray();
                foreach ($flema_data['list'] as $i => $flema_product) {
                    if (in_array($flema_product['url'], $history_detail_url_list)) {
                        $current_history_detail = HistoryDetail::where('product_id', $product->id)
                                                    ->where('flema', $flema_name)
                                                    ->where('url', $flema_product['url'])
                                                    ->first();
                        /*******************************************************************/
                        $current_history_detail->fill([
                            'oldest_to_latest_number' => $base_oldest_to_latest_number + $i
                        ])->save();
                        /*******************************************************************/
                        continue;
                    }

                    // 実際のフリマページにはあるが、DBにはない
                    $image_check = 0;
                    if ($flema_product['status'] === 1) {
                        $arguments2 = "{$product->product_id} '{$flema_product['image']}' CheckCardImg";
                        unset($outputs2);
                        exec("{$PYTHON3_PATH} {$EXECUTABLE_FILE} {$arguments2}", $outputs2);
                        $image_check = 1;
                        $flema_product['status'] = (int)$outputs2[0];
                    }
                    /********************************************************************/
                    HistoryDetail::forceCreate([
                        'product_id' => $product->id,
                        'flema' => $flema_name,
                        'price' => $flema_product['price'],
                        'title' => $flema_product['title'],
                        'url' => $flema_product['url'],
                        'img_url' => $flema_product['image'],
                        'status' => $flema_product['status'],
                        'image_check' => $image_check,
                        'oldest_to_latest_number' => $base_oldest_to_latest_number + $i,
                        'is_added_on' => $NOW_YMD
                    ]);
                    /********************************************************************/
                    $history_detail_url_list[] = $flema_product['url'];
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

                /*******************************************/
                HistoryDetailExecutionHistory::forceCreate([
                    'product_id' => $product->id,
                    'flema' => $flema_name
                ]);
                /*******************************************/
            }

            if ($scraping_num['yafuoku'] % 50 === 0 && $scraping_num['yafuoku'] !== 0) {
                Log::info('yafuoku...'.$scraping_num['yafuoku']);
            }
            if ($scraping_num['rakuma'] % 50 === 0 && $scraping_num['rakuma'] !== 0) {
                Log::info('rakuma...'.$scraping_num['rakuma']);
            }
            /*if ($scraping_num['yafuoku'] > $once_max_scraping_num
                || $scraping_num['rakuma'] > $once_max_scraping_num) {
                break;
            }*/
        }

        Log::info(
            "End updating price product-id: "
            ."{$start_scraping_product_id['yafuoku']} ~ {$end_scraping_product_id['yafuoku']} "
            ."(yafuoku Sum {$scraping_num['yafuoku']})"
        );
        Log::info(
            "End updating price product-id: "
            ."{$start_scraping_product_id['rakuma']} ~ {$end_scraping_product_id['rakuma']} "
            ."(rakuma Sum {$scraping_num['rakuma']})"
        );
    }
}
