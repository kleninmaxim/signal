<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Open Orders</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        .table {
            width: 100%;
            border: none;
            margin-bottom: 20px;
        }
        .table thead th {
            font-weight: bold;
            text-align: left;
            border: none;
            padding: 10px 15px;
            background: #d8d8d8;
            font-size: 14px;
        }
        .table thead tr th:first-child {
            border-radius: 8px 0 0 8px;
        }
        .table thead tr th:last-child {
            border-radius: 0 8px 8px 0;
        }
        .table tbody td {
            text-align: left;
            border: none;
            padding: 10px 15px;
            font-size: 14px;
            vertical-align: top;
        }
        .table tbody tr:nth-child(even){
            background: #f3f3f3;
        }
        .table tbody tr td:first-child {
            border-radius: 8px 0 0 8px;
        }
        .table tbody tr td:last-child {
            border-radius: 0 8px 8px 0;
        }
    </style>

</head>
<body>

<table class="table">
    <thead>
    <tr>
        <th>Symbol</th>
        <th>Size</th>
        <th>Entry Price</th>
        <th>Mark Price</th>
        <th>Liq. Price</th>
        <th>PNL</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($open_orders as $open_order)
        <tr>
            <td>{{$open_order['symbol']}}</td>
            <td>{{$open_order['notional']}}</td>
            <td>{{$open_order['entryPrice']}}</td>
            <td>{{$open_order['markPrice']}}</td>
            <td>{{$open_order['liquidationPrice']}}</td>
            <td>{{$open_order['unRealizedProfit']}}</td>
        </tr>
    @empty

    @endforelse
    </tbody>
</table>

</body>
</html>
