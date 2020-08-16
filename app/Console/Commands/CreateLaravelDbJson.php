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

class CreateLaravelDbJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-db-json:create';

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
        $NOW = Carbon::now();
        $table_data_list = array(
            'products' => Product::get(),
            'histories' => History::get(),
            'history_details' => HistoryDetail::get(),
            'history_detail_execution_histories' => HistoryDetailExecutionHistory::get(),
            'history_history_detail' => HistoryHistoryDetail::get()
        );

        foreach ($table_data_list as $table_name => $table_data) {
            $json = fopen(storage_path("app/public/{$table_name} {$NOW->year}-{$NOW->month}-{$NOW->day}.json"), 'w+b');
            fwrite($json, json_encode($table_data, JSON_UNESCAPED_UNICODE));
            fclose($json);
        }
    }
}
