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
        require app_path('Php/vardata.php');
        require app_path('Php/function.php');

        Log::info('attempting to update history ...');

        $histories = History::where('is_history_on', '>=', '2020-08-10')->get();
        foreach ($histories as $history) {
            /* oldest_to_latest_numberが重複していないかチェック */
            $each_count = array_count_values(
                HistoryDetail::where('product_id', $history->product_id)
                ->where('flema', $history->flea_market_name)
                ->where('oldest_to_latest_number', '!=', 0)
                ->pluck('oldest_to_latest_number')
                ->toArray()
            );
            if (count($each_count) > 0 && max($each_count) > 1) {
                Log::error(
                    "oldest-to-latest-number error , product-id: {$history->product_id} "
                    ."flema: '{$history->flea_market_name}'"
                );
                exit;
            }

            $latest_to_oldest_history_details = HistoryDetail::where('product_id', $history->product_id)
                                ->where('flema', $history->flea_market_name)
                                ->orderBy('oldest_to_latest_number', 'desc')
                                ->get(); // latest → oldest
            $max_oldest_to_latest_number = 0;
            $history_is_history_on = new Carbon($history->is_history_on);
            foreach ($latest_to_oldest_history_details as $i => $history_detail) {
                $detail_is_added_on = new Carbon($history_detail->is_added_on);
                if ($detail_is_added_on->lte($history_is_history_on)) {
                    $max_oldest_to_latest_number = $history_detail->oldest_to_latest_number;
                    break;
                }
            }

            $oldest_to_latest_success_history_details
                = HistoryDetail::where('product_id', $history->product_id)
                ->where('flema', $history->flea_market_name)
                ->orderBy('oldest_to_latest_number', 'asc')
                ->where('oldest_to_latest_number', '<=', $max_oldest_to_latest_number)
                ->where('status', 1)
                ->get();
            $latest_to_oldest_success_history_details
                = array_reverse($oldest_to_latest_success_history_details->toArray());
            $product_price_list = array();
            $product_id_list = array();
            foreach ($latest_to_oldest_success_history_details as $i => $success_history_detail) {
                $product_price_list[] = $success_history_detail['price'];
                $product_id_list[] = $success_history_detail['id'];
                if ($i >= $history->sample_num - 1) {
                    break;
                }
            }

            if (count($product_price_list) === 0) {
                $average_price = 0;
            } else {
                $average_price
                    = array_sum($product_price_list) / count($product_price_list);
            }

            /****************************************************/
            $history->fill([
                'average_price' => $average_price,
                'start_detail_id' => 0,
                'end_detail_id' => 0,
            ])->save();
            $history->historyDetails()->sync($product_id_list);
            /****************************************************/
        }
    }
}
