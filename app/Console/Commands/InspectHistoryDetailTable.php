<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use \App\Product; // add
use \App\History; // add
use Carbon\Carbon; // add
use \App\HistoryDetail; // add
use Illuminate\Support\Facades\Log; // add

class InspectHistoryDetailTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'history_details_table:inspect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect history_details table';

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

        // history_detailsテーブルと実際の検索結果を比較
        // history_detailsテーブルのstatusは、、、
        // historiesテーブルのstart_detail_idとend_detail_idに関しては点検しない
        // 前提: pythonプログラムが正しい
        
        require app_path('Php/vardata.php');
        require app_path('Php/function.php');
        
        $NOW_YMD = Carbon::now('Asia/Tokyo')->toDateString();
        $EXECUTABLE_FILE = app_path('Python/scrape.py');
        $scraping_num = array(
            'yafuoku' => 0,
            'rakuma' => 0
        );

        $START_PRODUCT_ID = 501;
        $END_PRODUCT_ID = 519;

        $inspection_object = array();

        Log::info(
            "Start inspecting history_details table , "
            ."product-id: {$START_PRODUCT_ID} ~ {$END_PRODUCT_ID}"
        );

        $products = Product::whereBetween('id', [$START_PRODUCT_ID, $END_PRODUCT_ID])->get();
        foreach ($products as $product) {
            foreach ($FLEMA_LIST as $flema_name) {
                //Log::error('error', ['object' => var_export($inspection_object, true)]);
                
                $arguments1 = "{$product->name}+{$product->product_id}"
                             ."+ドラゴンボールヒーローズ {$flema_name}";
                unset($outputs1);
                exec("{$PYTHON3_PATH} {$EXECUTABLE_FILE} {$arguments1}", $outputs1);
                $scraping_num[$flema_name]++;
                $FLEMA_DATA = json_decode($outputs1[0], true);


                $DB_PRODUCTS_EXIST = HistoryDetail::where('product_id', $product->id)
                                            ->where('flema', $flema_name)->exists();


                
                // IF pythonデータはある & dbデータが全くない
                // DO この全pythonデータをdbに挿入
                if (count($FLEMA_DATA['list']) !== 0 && !$DB_PRODUCTS_EXIST) {
                    foreach ($FLEMA_DATA['list'] as $flema_product) { // oldest → latest
                        $inspection_object[$product->id][$flema_name]
                        ['history_details_table']['created'][] = array(
                            'product_id' => $product->id,
                            'flema' => $flema_name,
                            'price' => $flema_product['price'],
                            'title' => $flema_product['title'],
                            'url' => $flema_product['url'],
                            'img_url' => $flema_product['image'],
                            'status' => $flema_product['status'],
                        );
                    }
                    Log::info("completed product-id: {$product->id}, flema-name: '{$flema_name}'");
                    continue; // 明示
                } elseif (count($FLEMA_DATA['list']) !== 0) {
                    $SCRAPING_PRODUCTS_URL_LIST = array_column($FLEMA_DATA['list'], 'url');
                    $all_history_details = HistoryDetail::where('product_id', $product->id)
                                            ->where('flema', $flema_name)->get();
                    $all_db_products_url_list = $all_history_details->pluck('url')->toArray();

                    // スクレイピングデータの最も古い商品がDBにない場合は色々めんどいため全削除
                    if (!in_array($SCRAPING_PRODUCTS_URL_LIST[0], $all_db_products_url_list)) {
                        $inspection_object[$product->id][$flema_name]
                        ['history_details_table']['deleted_id'] = $all_history_details->pluck('id');
                        foreach ($FLEMA_DATA['list'] as $scraping_product) {
                            $inspection_object[$product->id][$flema_name]
                            ['history_details_table']['created'][] = array(
                                'product_id' => $product->id,
                                'flema' => $flema_name,
                                'price' => $scraping_product['price'],
                                'title' => $scraping_product['title'],
                                'url' => $scraping_product['url'],
                                'img_url' => $scraping_product['image'],
                                'status' => $scraping_product['status'], // statusは適当 後から変更可
                            );
                        }
                        Log::info("completed product-id: {$product->id}, flema-name: '{$flema_name}'");
                        continue;
                    }

                    $scraping_oldest_product_id =
                        $all_history_details->where('url', $SCRAPING_PRODUCTS_URL_LIST[0])
                        ->first()->id;
                    
                    $db_products_or_after_scraping_oldest_product
                        =  HistoryDetail::where('product_id', $product->id)
                            ->where('flema', $flema_name)
                            ->where('id', '>=', $scraping_oldest_product_id)
                            ->get();

                    // スクレイピングデータ、DBデータどちらも存在し、
                    // スクレイピングデータの最も古い商品がDBにあることが確認できた

                    /* ここから、新しいロジックに変える */
                    /* スクレイピングした配列の各urlとDB配列の各urlを同じにするのを試みる */
                    /* pythonデータは変えない */

                    // 1. スクレイピングデータにはないが、DBにはある商品を削除
                    $db_products_1 = array();
                    foreach ($db_products_or_after_scraping_oldest_product as $history_detail) {
                        if (!in_array($history_detail['url'], $SCRAPING_PRODUCTS_URL_LIST)) {
                            $inspection_object[$product->id][$flema_name]
                            ['history_details_table']['deleted_id'][]
                                = $history_detail->id;
                        } else {
                            $db_products_1[] = $history_detail;
                        }
                    }

                    $DB_PRODUCTS_1_URL_LIST = array_column($db_products_1, 'url');
                    if ($SCRAPING_PRODUCTS_URL_LIST === $DB_PRODUCTS_1_URL_LIST) {
                        Log::info("completed product-id: {$product->id}, flema-name: '{$flema_name}'");
                        continue;
                    }

                    // 2. スクレイピングデータにはあるが、DBにはない商品をDB末尾に追加
                    $db_products_2 = $db_products_1;
                    foreach ($FLEMA_DATA['list'] as $scraping_product) {
                        if (!in_array($scraping_product['url'], $DB_PRODUCTS_1_URL_LIST)) {
                            $inspection_object[$product->id][$flema_name]
                            ['history_details_table']['created'][] = array(
                                'product_id' => $product->id,
                                'flema' => $flema_name,
                                'price' => $scraping_product['price'],
                                'title' => $scraping_product['title'],
                                'url' => $scraping_product['url'],
                                'img_url' => $scraping_product['image'],
                                'status' => $scraping_product['status'], // statusは適当 後から変更可
                            );
                            $db_products_2[] = $scraping_product;
                        }
                    }

                    $DB_PRODUCTS_2_URL_LIST = array_column($db_products_2, 'url');
                    if ($SCRAPING_PRODUCTS_URL_LIST === $DB_PRODUCTS_2_URL_LIST) {
                        Log::info("completed product-id: {$product->id}, flema-name: '{$flema_name}'");
                        continue;
                    }

                    // 3. 修復不可能なためDBデータを全削除して、スクレイピングデータを挿入
                    $inspection_object[$product->id][$flema_name]
                    ['history_details_table']['deleted_id']
                        = $db_products_or_after_scraping_oldest_product->pluck('id');
                    $inspection_object[$product->id][$flema_name]
                    ['history_details_table']['created'] = array(); //初期化
                    foreach ($FLEMA_DATA['list'] as $scraping_product) {
                        $inspection_object[$product->id][$flema_name]
                        ['history_details_table']['created'][] = array(
                            'product_id' => $product->id,
                            'flema' => $flema_name,
                            'price' => $scraping_product['price'],
                            'title' => $scraping_product['title'],
                            'url' => $scraping_product['url'],
                            'img_url' => $scraping_product['image'],
                            'status' => $scraping_product['status'], // statusは適当 後から変更可
                        );
                    }
                    Log::info("completed product-id: {$product->id}, flema-name: '{$flema_name}'");
                    continue; // 明示的

                // IF pythonデータが全く無いのに、dbにデータがある場合
                // DO db上のこの商品のデータを全削除
                } elseif ($DB_PRODUCTS_EXIST) {
                    //Log::error("exist error, product-id: {$product->id} flema: '{$flema_name}'");
                    $inspection_object[$product->id][$flema_name]
                    ['history_details_table']['deleted_id']
                        = $all_history_details->pluck('id');
                    Log::info("completed product-id: {$product->id}, flema-name: '{$flema_name}'");
                    continue; // 明示
                }
            }
        }

        // $inspection_objectをもとに実際にデータ削除、データ作成をする
        foreach ($inspection_object as $products) {
            foreach ($products as $flema_products) {
                if (!empty($flema_products['history_details_table']['deleted_id'])) {
                    HistoryDetail::destroy($flema_products['history_details_table']['deleted_id']);
                }
                if (!empty($flema_products['history_details_table']['created'])) {
                    foreach ($flema_products['history_details_table']['created'] as $create_product) {
                        HistoryDetail::forceCreate($create_product);
                    }
                }
            }
        }

        //Log::info("No problems found!!");
        Log::notice("inspection-object\n".var_export($inspection_object, true));
    }
}
