<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use \App\Product; // add
use \App\History; // add
use Carbon\Carbon; // add
use \App\HistoryDetail; // add
use Illuminate\Support\Facades\Log; // add

class UpdatePrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateprice:info';

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
        Log::info('Start updating price');
        
        require app_path('Php/vardata.php');
        require app_path('Php/function.php');

        $products = Product::get();
        $scraping_num = array(
            'yafuoku' => 0,
            'rakuma' => 0
        );

        $start_scraping_product_id = array(
            'yafuoku' => 0,
            'rakuma' => 0
        );

        $SAMPLE_NUM_LIST = [1, 5, 10];
        $NOW_YMD = Carbon::now('Asia/Tokyo')->toDateString();
        $EXECUTABLE_FILE = app_path('Python/scrape.py');

        foreach ($products as $product) {
            $history_details = array(
                'yafuoku' => HistoryDetail::where('product_id', $product->id)
                    ->where('flema', 'yafuoku')->get(),
                'rakuma' => HistoryDetail::where('product_id', $product->id)
                    ->where('flema', 'rakuma')->get()
            );
            $history_details_url = array(
                'yafuoku' => array(),
                'rakuma' => array()
            );
            foreach ($history_details['yafuoku'] as $yafuoku_history_detail) {
                $history_details_url['yafuoku'][] = $yafuoku_history_detail->url;
            }
            foreach ($history_details['rakuma'] as $rakuma_history_detail) {
                $history_details_url['rakuma'][] = $rakuma_history_detail->url;
            }

            /* main */
            /* data['yafuoku'] → data['rakuma'] */
            foreach ($FLEMA_LIST as $flema_name) {
                $doesnt_exist_sample_num = array();
                foreach ($SAMPLE_NUM_LIST as $sample_num) {
                    $doesnt_exist_sample_num["{$sample_num}"]
                        = History::where('product_id', $product->id)
                            ->where('flea_market_name', $flema_name)
                            ->whereDate('created_at', $NOW_YMD)
                            ->where('sample_num', $sample_num)->doesntExist();
                }

                if (!$doesnt_exist_sample_num['1']
                    && !$doesnt_exist_sample_num['5']
                    && !$doesnt_exist_sample_num['10']) {
                    continue;
                }

                
                $arguments1 = "{$product->name}+{$product->product_id}+ドラゴンボールヒーローズ {$flema_name}";
                unset($outputs1);
                exec("{$PYTHON3_PATH} {$EXECUTABLE_FILE} {$arguments1}", $outputs1);
                if ($start_scraping_product_id[$flema_name] === 0) {
                    $start_scraping_product_id[$flema_name] = $product->id;
                }
                $end_scraping_product_id[$flema_name] = $product->id;
                $scraping_num[$flema_name]++;
                $flema_data = json_decode($outputs1[0], true);


                
                //history details
                //oldest → latest
                foreach ($flema_data['list'] as $i => $flema_product) {
                    if (in_array($flema_product['url'], $history_details_url[$flema_name])) {
                        continue;
                    }


                    //title_errorではない場合(title_errorの場合は問答無用でstatus=0)
                    //高速化のため可読性を犠牲
                    
                    if ($flema_product['status'] !== 0) {
                        $arguments2 = "{$product->product_id} '{$flema_product['image']}' CheckCardImg";
                        unset($outputs2);
                        exec("{$PYTHON3_PATH} {$EXECUTABLE_FILE} {$arguments2}", $outputs2);
                        $flema_product['status'] = (int)$outputs2[0]; // 0 or 1
                    }
                    HistoryDetail::forceCreate([
                        'history_id' => 0,
                        'product_id' => $product->id,
                        'flema' => $flema_name,
                        'price' => $flema_product['price'],
                        'title' => $flema_product['title'],
                        'url' => $flema_product['url'],
                        'img_url' => $flema_product['image'],
                        'status' => $flema_product['status']
                    ]);
                    $history_details_url[$flema_name][] = $flema_product['url'];
                }
                if (count($flema_data['list']) > 0) { // 可読性が良くない
                    $start_product_url = $flema_data['list'][0]['url'];
                    $start_history_detail_id
                        = HistoryDetail::where('product_id', $product->id)
                            ->where('flema', $flema_name)
                            ->where('url', $start_product_url)
                            ->first()->id;
                    $flema_data_list_num = count($flema_data['list']);
                    $end_product_url = $flema_data['list'][$flema_data_list_num - 1]['url'];
                    $end_history_detail_id
                        = HistoryDetail::where('product_id', $product->id)
                            ->where('flema', $flema_name)
                            ->where('url', $end_product_url)
                            ->first()->id;
                } else {
                    $start_history_detail_id = 0;
                    $end_history_detail_id = 0;
                }


                /* 保守 今は気にしない*/
                /*if ($start_history_detail_id > $end_history_detail_id) {
                    logger()->info(
                        "exit error, updating price of product id: {$product->id}, "
                        ."flema name: {$flema_name} "
                        ."(start_detail_id: {$start_history_detail_id}, "
                        ."end_detail_id: {$end_history_detail_id})"
                    );
                    exit;
                }*/
                
                //以下別ファイルに移動
                //各サンプル数での平均価格を求める。これも別ファイルに
                ///statusが変更されたため各sample_numでのaverage_priceを再計算;
                //計算方法は多分合ってる
                $flema_data_list_reverse = array_reverse($flema_data['list']);
                //dd($flema_data_list_reverse);
                $status_array = array_column($flema_data_list_reverse, 'status');
                //dd($status_array);
                $success_products_index = array_keys($status_array, 1);
                //dd($success_products_index);
                $sample_num_array = array('1' => array(), '5' => array(), '10' => array());

                foreach ($success_products_index as $success_product_index) {
                    foreach ($SAMPLE_NUM_LIST as $sample_num) {
                        if (count($sample_num_array["{$sample_num}"]) >= $sample_num) {
                            continue;
                        }
                        $sample_num_array["{$sample_num}"][]
                            = $flema_data_list_reverse[$success_product_index]['price'];
                    }
                }
                //dd($sample_num_array);
                foreach ($SAMPLE_NUM_LIST as $sample_num) {
                    if (count($sample_num_array["{$sample_num}"]) === 0) {
                        $flema_data['average_prices']["sample_num_{$sample_num}"] = 0;
                        continue;
                    }
                    $flema_data['average_prices']["sample_num_{$sample_num}"]
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
                        'average_price' => (int)$flema_data['average_prices']
                                ["sample_num_{$sample_num}"],
                        'flea_market_name' => $flema_name,
                        'sample_num' => $sample_num,
                        'start_detail_id' => $start_history_detail_id,
                        'end_detail_id' => $end_history_detail_id
                    ]);
                }
                // products
                if ($flema_name === 'yafuoku') {
                    // only yafuoku price ;
                    $product->fill([
                        'average_price' => (int)$flema_data['average_prices']
                                            ['sample_num_10'],
                    ])->save();
                }
            }

            if ($scraping_num['yafuoku'] % 50 === 0 && $scraping_num['yafuoku'] !== 0) {
                Log::info('yafuoku...'.$scraping_num['yafuoku']);
            }
            if ($scraping_num['rakuma'] % 50 === 0 && $scraping_num['rakuma'] !== 0) {
                Log::info('rakuma...'.$scraping_num['rakuma']);
            }
            if ($scraping_num['yafuoku'] > $once_max_scraping_num
                || $scraping_num['rakuma'] > $once_max_scraping_num) {
                break;
            }
        }

        Log::info(
            "End updating price product-id: "
            ."{$start_scraping_product_id['yafuoku']} ~ "
            ."{$end_scraping_product_id['yafuoku']} "
            ."(yafuoku Sum {$scraping_num['yafuoku']})"
        );
        Log::info(
            "End updating price product-id: "
            ."{$start_scraping_product_id['rakuma']} ~ "
            ."{$end_scraping_product_id['rakuma']} "
            ."(rakuma Sum {$scraping_num['rakuma']})"
        );
    }
}
