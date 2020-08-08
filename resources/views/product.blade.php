@extends('layouts.navbar')

@section('content')
<canvas id="price-chart"></canvas>
<p>検索ワード {{ $keywords }}</p>
<h1>カードID {{ $product_id }}</h1>
<h1>ラクマ平均価格 {{ $average_price['rakuma'] }}</h1>
<h1>ヤフオク平均価格 {{ $average_price['yafuoku'] }}</h1>
<?php $pattern = '/https?:\/{2}[\w\/:%#\$&\?\(\)~\.=\+\-]+/'; ?>

@foreach($average_price_calculation_result as $flema => $aaa)
    @foreach($aaa as $time => $bbb)
        <p>{{ $flema }}</p>
        {{ $time }}
        {{ $bbb }}
    @endforeach
@endforeach
<h1>ラクマ</h1>
<table class="table table-bordered">
    <thead class="thead-dark">
        <tr>
            <th scope="col">ラクマ</th>
            <th scope="col">画像</th>
            <th scope="col">価格</th>
            <th scope="col">タイトル</th>
            <th scope="col">at</th>
            <th scope="col">status</th>
        </tr>
    <thead>
    <tbody>
        @foreach($history_details['rakuma'] as $i => $rakuma_history_detail)
            <tr>
                <th scope="row">{{ $i }}</th>
                <td><a href="{{$rakuma_history_detail->url}}"><img src="{{$rakuma_history_detail->img_url}}" style="width:100px;height:auto;"></a></td>
                <td>{{ $rakuma_history_detail->price }}</td>
                <td>{{ $rakuma_history_detail->title }}</td>
                <td>{{ $rakuma_history_detail->created_at->format('Y/m/d') }}</td>
                <td>{{ $rakuma_history_detail->status }}</td>
            </tr>
        @endforeach        
    </tbody>
</table>

<h1>ヤフオク</h1>
<table class="table  table-bordered">
    <thead class="thead-dark">
        <tr>
            <th scope="col">ヤフオク</th>
            <th scope="col">画像</th>
            <th scope="col">価格</th>
            <th scope="col">タイトル</th>
            <th scope="col">at</th>
            <th scope="col">status</th>
        </tr>
    <thead>
    <tbody>
        @foreach($history_details['yafuoku'] as $j => $yafuoku_history_detail)
            <tr>
                <th scope="row">{{ $j }}</th>
                <td><a href="{{$yafuoku_history_detail->url}}"><img src="{{$yafuoku_history_detail->img_url}}" style="width:100px;height:auto;"></a></td>
                <td>{{ $yafuoku_history_detail->price }}</td>
                <td>{{ $yafuoku_history_detail->title }}</td>
                <td>{{ $yafuoku_history_detail->created_at->format('Y/m/d') }}</td>
                <td>{{ $yafuoku_history_detail->status }}</td>
            </tr>
        @endforeach        
    </tbody>
</table>

<script>
    const ctx = document.getElementById('price-chart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($times),
            datasets: [
                {
                    label: 'ヤフオク',
                    data: @json($average_prices['yafuoku']),
                    fill:false, 
                },
                {
                    label: 'ラクマ',
                    data: @json($average_prices['rakuma']),
                    fill:false, 
                },
                {
                    label: 'ヤフオク回帰直線',
                    data: @json($regression_line_array['yafuoku']),
                    pointRadius: 0,
                    pointHitRadius: 0,
                    fill:false, 
                },
                {
                    label: 'ラクマ回帰直線',
                    data: @json($regression_line_array['rakuma']),
                    pointRadius: 0,
                    pointHitRadius: 0,
                    fill:false, 
                }
            ]
        }
    });
</script>
@endsection