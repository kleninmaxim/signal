<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Open Orders</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{asset('css/style.css')}}" type="text/css" />

</head>
<body>

<table class="table">
    <thead>
    <tr>
        <th>Symbol</th>
        <th>Size</th>
        <th>PNL</th>
        <th>Entry Price</th>
        <th>Mark Price</th>
        <th>Liq. Price</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($open_orders as $open_order)
        <tr>
            <td>{{$open_order['symbol']}}</td>
            <td>{{App\Hiney\Src\Math::round($open_order['notional'])}} USDT</td>
            <td>{{App\Hiney\Src\Math::round($open_order['unRealizedProfit'])}} USDT</td>
            <td>{{$open_order['entryPrice']}}</td>
            <td>{{$open_order['markPrice']}}</td>
            <td>{{$open_order['liquidationPrice']}}</td>
        </tr>
    @empty

    @endforelse
    </tbody>
</table>

</body>
</html>
