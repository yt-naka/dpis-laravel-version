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

        //ProductHistory::truncate();

        
        logger()->info('Start updating price');
        
        require app_path('Php/vardata.php');
        require app_path('Php/function.php');

        $products = Product::get();
        $scraping_num = array(
            'yafuoku' => 0,
            'rakuma' => 0
        );

        $SAMPLE_NUM_LIST = [1, 5, 10];
        $NOW_YMD = Carbon::now('Asia/Tokyo')->toDateString();

        $products = Product::where('product_id', 'like', '%+CP')->get();

        /*
        history_detailsテーブル初期データ挿入プログラム 多分、今後使わない
        foreach ($products as $product) {
            foreach ($FLEMA_LIST as $flema_name) {
                if (HistoryDetail::where('product_id', $product->id)
                    ->where('flema', $flema_name)->doesntExist()) {
                    $url = app_path('Python/scrape.py').' '
                            .'+'.$product->product_id.'+ドラゴンボールヒーローズ';
                    unset($outputs);
                    exec($python3_path.' '.$url.' '.$flema_name, $outputs);
                    $scraping_num[$flema_name]++;
                    $flema_data = json_decode($outputs[0], true);

                    foreach ($flema_data['list'] as $i => $flema_product) {
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
                    }
                }
            }

            if ($scraping_num['yafuoku'] % 50 === 0 && $scraping_num['yafuoku'] !== 0) {
                logger()->info('yafuoku...'.$scraping_num['yafuoku']);
            }
            if ($scraping_num['rakuma'] % 50 === 0 && $scraping_num['rakuma'] !== 0) {
                logger()->info('rakuma...'.$scraping_num['rakuma']);
            }
            if ($scraping_num['yafuoku'] > $once_max_scraping_num
                && $scraping_num['rakuma'] > $once_max_scraping_num) {
                    break;
            }
        }*/

        for ($i = 1; $i <= 88; $i++) {
            foreach ($FLEMA_LIST as $flema_name) {
                $histories = History::where('product_id', $i)
                    ->whereDate('created_at', $NOW_YMD)->where('flea_market_name', $flema_name)->get();

                $history_details = HistoryDetail::where('product_id', $i) /* oldest → latest */
                        ->where('flema', $flema_name)
                        ->whereBetween('id', [$histories[0]['start_detail_id'], $histories[0]['end_detail_id']])
                        ->get()->toArray();
                $history_details_reverse = array_reverse($history_details); /* latest → oldest */
                $status_array = array_column($history_details_reverse, 'status');
                $success_products_index = array_keys($status_array, 1);
                $sample_num_array = array('1' => array(), '5' => array(), '10' => array());
                $sample_num_average_price_array = array();

                foreach ($success_products_index as $success_product_index) {
                    foreach ($SAMPLE_NUM_LIST as $sample_num) {
                        if (count($sample_num_array["{$sample_num}"]) >= $sample_num) {
                            continue;
                        }
                        $sample_num_array["{$sample_num}"][]
                            = $history_details_reverse[$success_product_index]['price'];
                    }
                }
                foreach ($SAMPLE_NUM_LIST as $sample_num) {
                    if (count($sample_num_array["{$sample_num}"]) === 0) {
                        $sample_num_average_price_array["{$sample_num}"] = 0;
                        continue;
                    }
                    $sample_num_average_price_array["{$sample_num}"]
                        = array_sum($sample_num_array["{$sample_num}"])
                            / count($sample_num_array["{$sample_num}"]);
                }

                $histories[0]->fill([
                    'average_price' => $sample_num_average_price_array["1"]
                ])->save();
                $histories[1]->fill([
                    'average_price' => $sample_num_average_price_array["5"]
                ])->save();
                $histories[2]->fill([
                    'average_price' => $sample_num_average_price_array["10"]
                ])->save();
            }
        }


        /* history_detailsテーブルのstatusを更新 多分、後々使う */
        /*
        $products = Product::where('product_id', 'like', '%+CP')->get();
        foreach ($products as $product) {
            foreach ($FLEMA_LIST as $flema_name) {
                $url = app_path('Python/scrape.py').' '
                .'+'.$product->product_id.'+ドラゴンボールヒーローズ';
                unset($outputs);
                exec($python3_path.' '.$url.' '.$flema_name, $outputs);
                $scraping_num[$flema_name]++;
                $flema_data = json_decode($outputs[0], true);

                foreach ($flema_data['list'] as $i => $flema_product) {
                    $history_detail = HistoryDetail::where('product_id', $product->id)
                        ->where('flema', $flema_name)
                        ->where('url', $flema_product['url'])
                        ->first();
                    $history_detail->fill([
                        'status' => $flema_product['status'],
                    ])->save();
                }
            }
        }*/

        /*
        $products = Product::get();
        foreach($products as $product){
            $yafuoku_oldest_history_average_price = History::where('product_id', $product->id)->where('flea_market_name', 'yafuoku')->first()->average_price;
            $yafuoku_latest_history_average_price = History::where('product_id', $product->id)
                ->where('flea_market_name', 'yafuoku')->where('sample_num', 10)->orderBy('created_at', 'desc')->first()->average_price;
            $rakuma_oldest_history_average_price = History::where('product_id', $product->id)->where('flea_market_name', 'rakuma')->first()->average_price;
            $rakuma_latest_history_average_price = History::where('product_id', $product->id)
                ->where('flea_market_name', 'rakuma')->where('sample_num', 10)->orderBy('created_at', 'desc')->first()->average_price;
            if($yafuoku_oldest_history_average_price !== 0){
                $yafuoku_change_rate = ($yafuoku_latest_history_average_price - $yafuoku_oldest_history_average_price) / $yafuoku_oldest_history_average_price;
            }else{
                $yafuoku_change_rate = 0;
            }
            if($rakuma_oldest_history_average_price !== 0){
                $rakuma_change_rate = ($rakuma_latest_history_average_price - $rakuma_oldest_history_average_price) / $rakuma_oldest_history_average_price;
            }else{
                $rakuma_change_rate = 0;
            }

            $product->fill([
                'change_rate' => $yafuoku_change_rate,
            ])->save();
        }*/
        /*
        $products = Product::get();
        foreach($products as $product){
            $histories = History::where('product_id', $product->id)->get();
            foreach($histories as $history){
                $history_details = HistoryDetail::where('history_id', $history->id)->get();
                foreach($history_details as $history_detail){
                    $history_detail->fill([
                        'flema' => $history->flea_market_name
                    ])->save();
                }
            }
        }
        /*
        $products = Product::get();
        foreach($products as $product){
            $histories = History::where('product_id', $product->id)->get();
            $aaa = array();
            foreach($histories as $history){
                if($history->sample_num === 5 || $history->sample_num === 10){
                    continue;
                }
                $history_details = HistoryDetail::where('history_id', $history->id)->get();
                foreach($history_details as $history_detail){
                    if(in_array($history_detail->url, $aaa)){
                        HistoryDetail::destroy($history_detail->id);
                    }else{
                        $aaa[] = $history_detail->url;
                    }
                }
            }
        }
        /*
        $products = Product::get();
        $history_details = HistoryDetail::whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())->get();
        foreach($history_details as $history_detail){
            if(History::where('id', $history_detail->history_id)->doesntExist()){
                $history_detail->delete();
            }
        }*/
        /*
        $products = Product::get();
        foreach($products as $product){
            
            $yafuoku_sample1_history = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())->where('flea_market_name', 'yafuoku')->where('sample_num', 1)->first();
            $rakuma_sample1_history = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())->where('flea_market_name', 'rakuma')->where('sample_num', 1)->first();
            $yafuoku_sample5_history = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())->where('flea_market_name', 'yafuoku')->where('sample_num', 5)->first();
            $rakuma_sample5_history = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())->where('flea_market_name', 'rakuma')->where('sample_num', 5)->first();
            $yafuoku_sample10_history = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())->where('flea_market_name', 'yafuoku')->where('sample_num', 10)->first();
            $rakuma_sample10_history = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())->where('flea_market_name', 'rakuma')->where('sample_num', 10)->first();


            $yafuoku_sample1_history_details_num = HistoryDetail::where('history_id', $yafuoku_sample1_history->id)->count();
            $rakuma_sample1_history_details_num = HistoryDetail::where('history_id', $rakuma_sample1_history->id)->count();
            $yafuoku_sample5_history_details_num = HistoryDetail::where('history_id', $yafuoku_sample5_history->id)->count();
            $rakuma_sample5_history_details_num = HistoryDetail::where('history_id', $rakuma_sample5_history->id)->count();
            $yafuoku_sample10_history_details_num = HistoryDetail::where('history_id', $yafuoku_sample10_history->id)->count();
            $rakuma_sample10_history_details_num = HistoryDetail::where('history_id', $rakuma_sample10_history->id)->count();


            if($yafuoku_sample1_history_details_num === 0 && $yafuoku_sample1_history->average_price !== 0){
                dd($yafuoku_sample1_history->id.' yafuoku sample1 error');
            }
            if($yafuoku_sample5_history_details_num !== 0){
                dd($yafuoku_sample5_history->id.' yafuoku sample5 error');
            }
            if($yafuoku_sample10_history_details_num !== 0){
                dd($yafuoku_sample10_history->id.' yafuoku sample10 error');
            }
            if($rakuma_sample1_history_details_num === 0 && $rakuma_sample1_history->average_price !== 0){
                dd($rakuma_sample1_history->id.' rakuma error');
            }
            if($rakuma_sample5_history_details_num !== 0){
                dd($rakuma_sample5_history->id.' rakuma sample5 error');
            }
            if($rakuma_sample10_history_details_num !== 0){
                dd($rakuma_sample10_history->id.' rakuma sample10 error');
            }
        }
        /*
        $products = Product::get();
        foreach($products as $product){
            $yafuoku_sample1_latest_histories = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())
            ->where('flea_market_name', 'yafuoku')->where('sample_num', 1)->get();
            $rakuma_sample1_latest_histories = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())
            ->where('flea_market_name', 'rakuma')->where('sample_num', 1)->get();
            $yafuoku_sample5_latest_histories = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())
            ->where('flea_market_name', 'yafuoku')->where('sample_num', 5)->get();
            $rakuma_sample5_latest_histories = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())
            ->where('flea_market_name', 'rakuma')->where('sample_num', 5)->get();
            $yafuoku_sample10_latest_histories = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())
            ->where('flea_market_name', 'yafuoku')->where('sample_num', 10)->get();
            $rakuma_sample10_latest_histories = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())
            ->where('flea_market_name', 'rakuma')->where('sample_num', 10)->get();
            if(count($yafuoku_sample1_latest_histories) === 2){
                $yafuoku_sample1_latest_histories[1]->delete();
            }
            if(count($rakuma_sample1_latest_histories) === 2){
                $rakuma_sample1_latest_histories[1]->delete();
            }
            if(count($yafuoku_sample5_latest_histories) === 2){
                $yafuoku_sample5_latest_histories[1]->delete();
            }
            if(count($rakuma_sample5_latest_histories) === 2){
                $rakuma_sample5_latest_histories[1]->delete();
            }
            if(count($yafuoku_sample10_latest_histories) === 2){
                $yafuoku_sample10_latest_histories[1]->delete();
            }
            if(count($rakuma_sample10_latest_histories) === 2){
                $rakuma_sample10_latest_histories[1]->delete();
            }

            $yafuoku_sample1_latest_histories = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())
            ->where('flea_market_name', 'yafuoku')->get();
            $rakuma_sample1_latest_histories = History::where('product_id', $product->id)
            ->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())
            ->where('flea_market_name', 'rakuma')->get();
            if(count($yafuoku_sample1_latest_histories) === 3){
                dd('error');
            }
            if(count($rakuma_sample1_latest_histories) === 3){
                dd('error');
            }
        }

        /*
        $product = Product::find(131);
        $product->forceFill([
            'product_id' => 'HGD7-SEC',
        ])->save();
        /*
        
        require(app_path('Php/vardata.php'));
        exec($python2_path.' '.app_path('Python/get_all_card_info_for_seeder.py'), $products_infos);
        
        for ($i = 0 ; $i < count($products_infos); $i = $i + 4) {
            Product::forceCreate([
                'product_id' => $products_infos[$i],
                'name' => $products_infos[$i+1],
                'average_price' => 0,
                'rarity' => 'SR', //適宜変える
                'mission' => $products_infos[$i+2],
                'model' => $products_infos[$i+3],
            ]);
        }

        /*
        $products = Product::get();
        foreach($products as $product){
            $rakuma_history = History::where('product_id', $product->id)->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())->where('flea_market_name', 'rakuma')->first();
            $yafuoku_history = History::where('product_id', $product->id)->whereDate('created_at', Carbon::now('Asia/Tokyo')->toDateString())->where('flea_market_name', 'yafuoku')->first();
            $rakuma_history_details = HistoryDetail::where('history_id', $rakuma_history->id)->where('status', 1)->get();
            $yafuoku_history_details = HistoryDetail::where('history_id', $yafuoku_history->id)->where('status', 1)->get();

            $rakuma_price = 0;
            $yafuoku_price = 0;
            foreach($rakuma_history_details as $rakuma_history_detail){
                $rakuma_price += $rakuma_history_detail->price;
            }
            foreach($yafuoku_history_details as $yafuoku_history_detail){
                $yafuoku_price += $yafuoku_history_detail->price;
            }

            if(count($rakuma_history_details) === 0){
                $rakuma_price = 0;
            }else{
                $rakuma_price /= count($rakuma_history_details);                
            }
            if(count($yafuoku_history_details) === 0){
                $yafuoku_price = 0;
            }else{
                $yafuoku_price /= count($yafuoku_history_details);                
            }
            
            $rakuma_history->forceFill([
                'average_price' => $rakuma_price
            ])->save();
            $yafuoku_history->forceFill([
                'average_price' => $yafuoku_price
            ])->save();
        }
        */

        /*
        
        $products = Product::get();
        foreach ($products as $i => $product) {
            unset($price);
            $url = app_path('Python/update_price.py').' '.$product->name.'+'.$product->product_id.'+ドラゴンボールヒーローズ';
            exec($python2_path.' '.$url, $price);
            $product->fill([
                'average_price' => (int)$price[0],
            ])->save();
            History::forceCreate([
                'product_id' => $product->id,
                'average_price' => (int)$price[0],
                'flea_market_name' => 'yafuoku',
            ]);
        }
        */
    }
}
