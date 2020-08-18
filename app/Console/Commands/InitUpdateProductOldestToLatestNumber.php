<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use \App\Product; // add
use \App\History; // add
use Carbon\Carbon; // add
use \App\HistoryDetail; // add
use \App\HistoryDetailExecutionHistory; // add
use Illuminate\Support\Facades\Log; // add

class InitUpdateProductOldestToLatestNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oldest-to-latest-number:initupdate';

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

        $EXECUTABLE_FILE = app_path('Python/scrape.py');


        
        /* 全商品のoldest_to_latest_numberを0にする */
        $all_history_details = HistoryDetail::get();
        foreach ($all_history_details as $history_detail) {
            $history_detail->forceFill([
                'oldest_to_latest_number' => 0
            ])->save();
        }

        $products = Product::get();
        foreach ($products as $product) {
            foreach ($FLEMA_LIST as $flema_name) {
                $arguments1 = "{$product->name}+{$product->product_id}+"
                                ."ドラゴンボールヒーローズ {$flema_name}";
                unset($outputs1);
                exec("{$PYTHON3_PATH} {$EXECUTABLE_FILE} {$arguments1}", $outputs1);
                $flema_data = json_decode($outputs1[0], true);
                // oldest → latest 1ページ分
                $number = 1;
                foreach ($flema_data['list'] as $i => $flema_product) {
                    if (HistoryDetail::where('product_id', $product->id)
                        ->where('flema', $flema_name)->where('url', $flema_product['url'])
                        ->doesntExist()) {
                            continue;
                    }
                    $history_detail = HistoryDetail::where('product_id', $product->id)
                                    ->where('flema', $flema_name)
                                    ->where('url', $flema_product['url'])
                                    ->first();
                    
                    $history_detail->forceFill([
                        'oldest_to_latest_number' => $number
                    ])->save();
                    $number++;
                }
                Log::info("end product-id: {$product->id}, flema: '{$flema_name}'");
            }
        }
    }
}
