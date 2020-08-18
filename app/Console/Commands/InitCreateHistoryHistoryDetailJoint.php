<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use \App\Product; // add
use \App\History; // add
use Carbon\Carbon; // add
use \App\HistoryDetail; // add
use \App\HistoryDetailExecutionHistory; // add
use \App\HistoryHistoryDetail; // add
use Illuminate\Support\Facades\Log; // add

class InitCreateHistoryHistoryDetailJoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'history-history-detail-joint:initcreate';

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
            
            $detail_avg_price = (int)round($history->historyDetails()->avg('price'));
            print("history-id: {$history->id}, history-avg-price: {$history->average_price}, detail-avg-price: {$detail_avg_price}\n");
            if ($history->average_price !== $detail_avg_price) {
                exit;
            }
            /*$history->historyDetails()->attach($history_detail_id_list);
            //$int_detail_avg_price = (int)round($history_details->avg('price'));
            /*
            if ($history->average_price !== $int_detail_avg_price) {
                exit;
            }*/
            
            /*foreach ($history_details as $history_detail) {
                HistoryHistoryDetail::create([
                    'history_id' => $history->id,
                    'historyy_detail_id' => $history_detail->id
                ]);
            }*/
        }
    }
}
