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
            $history_details_url = array(
                'yafuoku' => HistoryDetail::where('product_id', $product->id)
                                ->where('flema', 'yafuoku')->pluck('url')->toArray(),
                'rakuma' => HistoryDetail::where('product_id', $product->id)
                                ->where('flema', 'rakuma')->pluck('url')->toArray()
            );

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
                
                //oldest → latest success-product 10個
                /*$base_oldest_to_latest_number = 0;
                foreach ($flema_data['list'] as $i => $flema_product) {
                    if (in_array($flema_product['url'], $history_details_url[$flema_name])) {
                        $base_oldest_to_latest_number = HistoryDetail::where('product_id', $product->id)
                                                        ->where('flema', $flema_name)
                                                        ->where('url', $flema_product['url'])
                                                        ->first()->oldest_to_latest_number;
                    }
                }
                if ($base_oldest_to_latest_number === 0) {
                    exit;
                }
                $flema_product_number = 0;*/
                foreach ($flema_data['list'] as $i => $flema_product) {
                    if (in_array($flema_product['url'], $history_details_url[$flema_name])) {
                        continue;
                    }

                    /*if ($flema_product['status'] === 1) {
                        $arguments2 = "{$product->product_id} '{$history_detail->img_url}' CheckCardImg";
                        unset($outputs2);
                        exec("{$PYTHON3_PATH} {$EXECUTABLE_FILE} {$arguments2}", $outputs2);
                        $flema_product['status'] = (int)$outputs2[0];
                    }*/

                    HistoryDetail::forceCreate([
                        'product_id' => $product->id,
                        'flema' => $flema_name,
                        'price' => $flema_product['price'],
                        'title' => $flema_product['title'],
                        'url' => $flema_product['url'],
                        'img_url' => $flema_product['image'],
                        'status' => $flema_product['status'], // statusは適当 あとから変更可
                        'oldest_to_latest_number' => 0,
                        'is_added_on' => $NOW_YMD
                    ]);
                    $history_details_url[$flema_name][] = $flema_product['url'];
                }

                HistoryDetailExecutionHistory::forceCreate([
                    'product_id' => $product->id,
                    'flema' => $flema_name
                ]);
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
