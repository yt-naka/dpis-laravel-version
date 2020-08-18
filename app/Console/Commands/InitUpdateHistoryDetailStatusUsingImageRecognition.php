<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Product; // add
use App\History; // add
use Carbon\Carbon; // add
use App\HistoryDetail; // add
use Illuminate\Support\Facades\Log; // add

class InitUpdateHistoryDetailStatusUsingImageRecognition extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'history-detail-status:update';

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

        Log::info('start');
        $history_details = HistoryDetail::get();
        foreach ($history_details as $history_detail) {
            if ($history_detail->id <= 18554) {
                continue;
            }
            if ($history_detail->status === 1 && $history_detail->image_check === 0) {
                $arguments2 = "{$history_detail->product->product_id} '{$history_detail->img_url}' CheckCardImg";
                unset($outputs2);
                exec("{$PYTHON3_PATH} {$EXECUTABLE_FILE} {$arguments2}", $outputs2);
                $status = (int)$outputs2[0];
                $history_detail->fill([
                    'status' => $status,
                    'image_check' => 1
                ])->save();
                Log::info("update status of history-detail-id: {$history_detail->id}");
                Log::info("status: {$status}");
            }
        }
        Log::info('end');
    }
}
