@extends('layouts.navbar')
@section('content')
@foreach($products as $product)
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
@endforeach

@endsection