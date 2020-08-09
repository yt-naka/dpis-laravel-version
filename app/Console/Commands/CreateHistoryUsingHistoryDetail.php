<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use \App\Product; // add
use \App\History; // add
use Carbon\Carbon; // add
use \App\HistoryDetail; // add
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

        Log::info('Start creating history using history detail');
        
        require app_path('Php/vardata.php');
        require app_path('Php/function.php');

        $SAMPLE_NUM_LIST = [1, 5, 10];
        $NOW_YMD = Carbon::now('Asia/Tokyo')->toDateString();


        $products = Product::get();
        foreach ($products as $product) {
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
                $start_history_detail_id = array('1' => 0, '5' => 0, '10' => 0);
                $end_history_detail_id = array('1' => 0, '5' => 0, '10' => 0);

                $history_details = HistoryDetail::where('product_id', $product->id)
                            ->where('flema', $flema_name)->get(); // 古 → 新
                $reverse_history_details = array_reverse($history_details); // 新 → 古
                //dd($flema_data_list_reverse);
                $status_array = array_column($reverse_history_details, 'status');
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
                            = $reverse_history_details[$success_product_index]['price'];
                        if ($start_history_detail_id["{$sample_num}"] === 0) {
                            $start_history_detail_id["{$sample_num}"]
                                = $reverse_history_details[$success_product_index]->id;
                        }
                        $end_history_detail_id["{$sample_num}"]
                            = $reverse_history_details[$success_product_index]->id;
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
                        'start_detail_id' => $start_history_detail_id["{$sample_num}"],
                        'end_detail_id' => $end_history_detail_id["{$sample_num}"]
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
        }
    }
}
