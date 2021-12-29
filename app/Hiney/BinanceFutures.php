<?php

namespace App\Hiney;

use Illuminate\Support\Facades\Http;
use JetBrains\PhpStorm\Pure;

class BinanceFutures
{

    private string $base_url;
    private string $public_api;
    private string $private_api;

    public function __construct()
    {

        $this->public_api = config('api.public_api');
        $this->private_api = config('api.private_api');
        $this->base_url = 'https://fapi.binance.com';

    }

    public function getContracts(): array
    {

        return Http::get($this->base_url . '/fapi/v1/exchangeInfo')->collect()->toArray();

    }

    public function getBalances(): array
    {

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->public_api
        ])->get(
            $this->base_url . '/fapi/v1/account',
            [
                'timestamp' => $this->getTimestamp(),
                'signature' => $this->generateSignature()
            ]
        )->collect()->toArray();

    }

    /*
    Array
    (
        [orderId] => 4887710177
        [symbol] => WAVESUSDT
        [status] => NEW
        [clientOrderId] => QRZTARXf9vKQd1iwzGyqUK
        [price] => 10
        [avgPrice] => 0.00000
        [origQty] => 10
        [executedQty] => 0
        [cumQty] => 0
        [cumQuote] => 0
        [timeInForce] => GTC
        [type] => LIMIT
        [reduceOnly] =>
        [closePosition] =>
        [side] => BUY
        [positionSide] => BOTH
        [stopPrice] => 0
        [workingType] => CONTRACT_PRICE
        [priceProtect] =>
        [origType] => LIMIT
        [updateTime] => 1640719229063
    )
    */
    public function createOrder($symbol, $side, $order_type, $quantity = null, $price = null, $stop_price = null, $close_position = null, $workingType = null): array
    {

        $query = http_build_query([
            'timestamp' => $this->getTimestamp(),
            'symbol' => $symbol,
            'type' => $order_type,
            'side' => $side
        ]);

        if (!empty($quantity))
            $query .= '&quantity=' . $quantity;

        if (!empty($price))
            $query .= '&price=' . $price;

        if ($order_type == 'LIMIT' || $order_type == 'STOP' || $order_type == 'TAKE_PROFIT')
            $query .= '&timeInForce=' . 'GTC';

        if (!empty($stop_price))
            $query .= '&stopPrice=' . $stop_price;

        if (!empty($close_position))
            $query .= '&closePosition=' . $close_position;

        if ($order_type == 'STOP_MARKET' || $order_type == 'TAKE_PROFIT_MARKET')
            $query .= '&workingType=' . $workingType;

        $query .= '&signature=' . $this->generateSignatureWithQuery($query);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->public_api,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->withBody($query, 'application/json')->post(
            $this->base_url . '/fapi/v1/order'
        )->collect()->toArray();

    }

/*
    Array
    (
        [orderId] => 4887710177
        [symbol] => WAVESUSDT
        [status] => CANCELED
        [clientOrderId] => QRZTARXf9vKQd1iwzGyqUK
        [price] => 10
        [avgPrice] => 0.00000
        [origQty] => 10
        [executedQty] => 0
        [cumQty] => 0
        [cumQuote] => 0
        [timeInForce] => GTC
        [type] => LIMIT
        [reduceOnly] =>
        [closePosition] =>
        [side] => BUY
        [positionSide] => BOTH
        [stopPrice] => 0
        [workingType] => CONTRACT_PRICE
        [priceProtect] =>
        [origType] => LIMIT
        [updateTime] => 1640719301118
    )

    Array
    (
        [code] => -2011
        [msg] => Unknown order sent.
    )
*/
    public function cancelOrder($order_id, $symbol): array
    {
        $query = http_build_query([
            'timestamp' => $this->getTimestamp(),
            'orderId' => $order_id,
            'symbol' => $symbol
        ]);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->public_api,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->withBody(
            $query . '&signature=' . $this->generateSignatureWithQuery($query),
            'application/json'
        )->delete($this->base_url . '/fapi/v1/order')->collect()->toArray();

    }

/*
    Array
    (
        [orderId] => 4887710177
        [symbol] => WAVESUSDT
        [status] => NEW
        [clientOrderId] => QRZTARXf9vKQd1iwzGyqUK
        [price] => 10
        [avgPrice] => 0.00000
        [origQty] => 10
        [executedQty] => 0
        [cumQuote] => 0
        [timeInForce] => GTC
        [type] => LIMIT
        [reduceOnly] =>
        [closePosition] =>
        [side] => BUY
        [positionSide] => BOTH
        [stopPrice] => 0
        [workingType] => CONTRACT_PRICE
        [priceProtect] =>
        [origType] => LIMIT
        [time] => 1640719229063
        [updateTime] => 1640719229063
    )
*/
    public function getOrderStatus($order_id, $symbol): array
    {

        $timestamp = $this->getTimestamp();

        $query = http_build_query([
            'timestamp' => $timestamp,
            'orderId' => $order_id,
            'symbol' => $symbol
        ]);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->public_api,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->get($this->base_url . '/fapi/v1/order', [
            'timestamp' => $timestamp,
            'orderId' => $order_id,
            'symbol' => $symbol,
            'signature' => $this->generateSignatureWithQuery($query)
        ])->collect()->toArray();

    }

    public function getAllOpenOrders($symbol): array
    {

        $timestamp = $this->getTimestamp();

        $query = http_build_query([
            'timestamp' => $timestamp,
            'symbol' => $symbol,
        ]);

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->public_api,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->get($this->base_url . '/fapi/v1/openOrders', [
            'timestamp' => $timestamp,
            'symbol' => $symbol,
            'signature' => $this->generateSignatureWithQuery($query)
        ])->collect()->toArray();

    }

    /*
    Array
    (
        [0] => Array
        (
        [symbol] => WAVESUSDT
        [positionAmt] => 2.0
        [entryPrice] => 14.7895
        [markPrice] => 14.79725859
        [unRealizedProfit] => 0.01551718
        [liquidationPrice] => 0
        [leverage] => 50
        [maxNotionalValue] => 5000
        [marginType] => cross
        [isolatedMargin] => 0.00000000
        [isAutoAddMargin] => false
        [positionSide] => BOTH
        [notional] => 29.59451718
        [isolatedWallet] => 0
        [updateTime] => 1640720914294
        )
    )
    */
    public function getPositionInformation($symbol = null): array
    {

        $timestamp = $this->getTimestamp();

        $query = 'timestamp=' . $timestamp;

        if (!empty($symbol)) {
            $query .= '&symbol=' . $symbol;
            $body = [
                'timestamp' => $timestamp,
                'symbol' => $symbol,
                'signature' => $this->generateSignatureWithQuery($query)
            ];
        } else {
            $body = [
                'timestamp' => $timestamp,
                'signature' => $this->generateSignatureWithQuery($query)
            ];
        }

        return Http::withHeaders([
            'X-MBX-APIKEY' => $this->public_api,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->get($this->base_url . '/fapi/v2/positionRisk', $body)->collect()->toArray();

    }

    #[Pure] private function generateSignature(): bool|string
    {

        return hash_hmac('sha256', 'timestamp=' . $this->getTimestamp(), $this->private_api);

    }

    private function generateSignatureWithQuery($query): string
    {

        return \hash_hmac('sha256', $query, $this->private_api);

    }

    private function getTimestamp(): string
    {

        list($msec, $sec) = explode(' ', microtime());

        return $sec . substr($msec, 2, 3);

    }

}
