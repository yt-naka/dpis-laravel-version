@extends('layouts.navbar')

@section('content')
<canvas id="change-rate"></canvas>

<script>
    const ctx = document.getElementById('change-rate').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($times),
            datasets: [
                {
                    label: 'ヤフオク',
                    data: @json($yafuoku_average_prices),
                    fill:false, 
                },
                {
                    label: 'ラクマ',
                    data: @json($rakuma_average_prices),
                    fill:false, 
                }
            ]
        }
    });
</script>
@endsection