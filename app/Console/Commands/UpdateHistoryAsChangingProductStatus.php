<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use \App\Product; // add
use \App\History; // add
use Carbon\Carbon; // add
use \App\HistoryDetail; // add
use \App\HistoryDetailExecutionHistory; // add
use Illuminate\Support\Facades\Log; // add

class UpdateHistoryAsChangingProductStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'history:update';

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
        $histories = History::where('is_history_on', '>=', '2020-08-10')->get();
        foreach ($histories as $history) {
            $success_history_details = HistoryDetail::where('product_id', $history->product_id)
                                ->where('flema', $history->flea_market_name)
                                ->where('id', '<=', $history->end_detail_id)
                                ->where('status', 1)->get(); // 古 → 新
            $reverse_success_history_details = array_reverse($success_history_details->toArray()); // 新 → 古

            $price_list = array();
            $start_history_detail_id = 0;
            $end_history_detail_id = 0;
            foreach ($reverse_success_history_details as $i => $success_history_detail) {
                $price_list[] = $success_history_detail['price'];
                if ($end_history_detail_id === 0) {
                    $end_history_detail_id = $success_history_detail['id'];
                }
                $start_history_detail_id = $success_history_detail['id'];
                if ($i >= $history->sample_num - 1) {
                    break;
                }
            }

            if (count($price_list) === 0) {
                $average_price = 0;
            } else {
                $average_price
                    = array_sum($price_list) / count($price_list);
            }

            /*$history->fill([
                'average_price' => $average_price,
                'start_detail_id' => $start_history_detail_id,
                'end_detail_id' => $end_history_detail_id,
            ])->save();*/
            $average_price_int = (int)round($average_price);
            if ($history->average_price !== $average_price_int) {
                //Log::info($history->id);
                print("history-id: {$history->id}, origin-average-price: {$history->average_price}, average-price, {$average_price_int}\n");
            }
            // products
            /*if ($ === 'yafuoku') {
                // only yafuoku price ;
                $product->fill([
                    'average_price' => $average_prices['sample_num_10'],
                ])->save();
            }*/
        }
    }
}
