<?php

use Illuminate\Database\Seeder;

use \App\Product; //add
use \App\History; //add
use Carbon\Carbon; //add
use \App\HistoryDetail; //add

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        /*
        dd(Str::random(17));
        dd(Carbon::now('Asia/Tokyo')->toDateString());
        /*
        $json = fopen('histories.json', 'w+b');
        //dd(History::get());
        fwrite($json, json_encode(History::get(), JSON_UNESCAPED_UNICODE));
        fclose($json);
        $histories = History::get();
        foreach ($histories as $history) {
            $date = $history->created_at->format('Y-m-d');
            $history->fill([
                'is_history_on' => $date
            ])->save();
        }*/
    }
}
