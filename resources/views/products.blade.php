@extends('layouts.navbar')

@section('content')
<canvas id="top-price-products"></canvas>
<canvas id="all-products-price-trends"></canvas>

<h1>{{ $average_price_sum }}円</h1>
<h1>{{ $product_num }}枚</h1>
<h1>Top 50</h1>

<div class="dropdown">
    <button class="btn btn-secondary dropdown-toggle float-right" type="button" data-toggle="dropdown">並び替え</button>
    <div class="dropdown-menu">         
        <a class="dropdown-item" href="{{ url('/products?desc=average_price') }}">平均価格(降順)</a>
        <a class="dropdown-item" href="{{ url('/products?asc=average_price') }}">平均価格(昇順)</a>
        <a class="dropdown-item" href="{{ url('/products?desc=change_rate') }}">変化率(降順)</a>
        <a class="dropdown-item" href="{{ url('/products?asc=change_rate') }}">変化率(昇順)</a>
        <a class="dropdown-item" href="{{ url('/products?oldest_to_latest=card_number') }}">カードナンバー(古→新)</a>
        <a class="dropdown-item" href="{{ url('/products?latest_to_oldest=card_number') }}">カードナンバー(新→古)</a>
    </div>
</div>

<table class="table table-bordered">
    <caption>一覧</caption>
    <thead class="thead-dark">
        <tr>
            <th scope="col">ランク</th>
            <th scope="col">画像</th>
            <th scope="col">平均価格</th>
            <th scope="col">変化率</th>
            <!--<th scope="col">ミッション名</th>-->
            <th scope="col">カード番号</th>
            <!--<th scope="col">レアリティ</th>-->
            <!--<th scope="col">機種名</th>-->
            
        </tr>
    <thead>
    <tbody>
        @foreach($products as $i => $product)
            @break($i === 50)
            <tr>
                <th scope="row">{{ $i + 1 }}</th>
                <td>
                    <a href="{{ url('/product', ['product_id' => $product->product_id, 'time' => $now_ymd]) }}">
                        @switch($product->model)
                            @case('DBH')
                                <img src="http://carddass.com/dbh/image/cardlist/dummys/{{ $product->product_id_for_img }}.jpg">  
                                @break
                            @case('SDBH')
                                <img src="http://carddass.com/dbh/sdbh_bm/images/cardlist/dummys/{{ $product->product_id_for_img }}.png">   
                                @break
                        @endswitch
                    </a>
                </td>
                <td>{{ $product->average_price }}</td>
                <td>{{ $product->change_rate }}</td>
                <!--<td>{{ $product->mission }}</td>-->
                <td>{{ $product->product_id }}</td>
                <!--<td>{{ $product->rarity }}</td>-->
                <!--<td>{{ $product->model }}</td>-->
            </tr>
        @endforeach        
    </tbody>
</table>

<script>

    function isSmartPhone() {
        if (navigator.userAgent.match(/iPhone|Android.+Mobile/)) {
            return true;
        } else {
            return false;
        }
    }
    const width = screen.width;
    const height = screen.height;
    const cardImg = document.getElementsByTagName('img');
    for (let i = 0; i < cardImg.length; i++) {
        if(isSmartPhone()){
            cardImg[i].style.width = `${width/7}px`;
            cardImg[i].style.height = 'out';
        }else{
            cardImg[i].style.width = `${width/10}px`;
            cardImg[i].style.height = 'out';
        }
    }

    const topPriceProductsContext = document.getElementById('top-price-products').getContext('2d');
    const allProductsPriceTrendsContext = document.getElementById('all-products-price-trends').getContext('2d');
    if(isSmartPhone()){
        topPriceProductsContext.canvas.height = height / 2;
        allProductsPriceTrendsContext.canvas.height = height / 2;
    }
    const topPriceProducts = new Chart(topPriceProductsContext, {
        type: 'line',
        data: {
            labels: @json($horizontal_line_card_number),
            datasets: [{
                data: @json($vertical_line_anything),
                backgroundColor: "rgba(0,0,0,0)"
            }]
        }
    });

    const allProductsPriceTrends = new Chart(allProductsPriceTrendsContext, {
        type: 'line',
        data: {
            labels: @json($horizontal_line_date),
            datasets: @json($vertical_line_average_price['yafuoku'])
        },
    })
</script>
@endsection