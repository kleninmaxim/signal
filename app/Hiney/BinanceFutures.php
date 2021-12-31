<?php

namespace App\Hiney;

use App\Hiney\Src\Telegram;
use App\Models\ErrorLog;
use Illuminate\Support\Facades\Http;
use JetBrains\PhpStorm\Pure;

class BinanceFutures
{

    private string $base_url;
    private string $public_api;
    private string $private_api;
    private Telegram $telegram;

    public function __construct()
    {

        $this->public_api = config('api.public_api');
        $this->private_api = config('api.private_api');
        $this->base_url = 'https://fapi.binance.com';
        $this->telegram = new Telegram();

    }

    public function getContracts(): array
    {

        return Http::get($this->base_url . '/fapi/v1/exchangeInfo')->collect()->toArray();

    }

    public function getBalances(): array|bool
    {

        for ($i = 0; $i < 5; $i++) {

            $balances = Http::withHeaders([
                'X-MBX-APIKEY' => $this->public_api
            ])->get(
                $this->base_url . '/fapi/v1/account',
                [
                    'timestamp' => $this->getTimestamp(),
                    'signature' => $this->generateSignature()
                ]
            )->collect()->toArray();

            if (
                !isset($balances['totalWalletBalance']) ||
                !isset($balances['assets']) ||
                !isset($balances['positions'])
            ) {

                usleep(100000);

                ErrorLog::create([
                    'title' => 'Can\'t get balances throw api. Tries: ' . $i,
                    'message' => json_encode($balances),
                ]);

                $this->telegram->send(
                    'Can\'t get balances throw api!!! Tries: ' . $i . '. JSON: ' . json_encode($balances) . "\n"
                );

            } else
                return $balances;

        }

        return false;

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
    public function createOrder(
        string $symbol,
        string $side,
        string $order_type,
        float $quantity = null,
        float $price = null,
        array $options = []
    ): array|bool
    {

        for ($i = 0; $i < 5; $i++) {

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

            if (in_array($order_type, ['LIMIT', 'STOP', 'TAKE_PROFIT']))
                $query .= '&timeInForce=' . 'GTC';

            if (isset($options['stop_price']))
                $query .= '&stopPrice=' . $options['stop_price'];

            if (isset($options['close_position']))
                $query .= '&closePosition=' . $options['close_position'];

            if (isset($options['working_type']))
                $query .= '&workingType=' . $options['working_type'];

            $query .= '&signature=' . $this->generateSignatureWithQuery($query);

            $create_order = Http::withHeaders([
                'X-MBX-APIKEY' => $this->public_api,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->withBody(
                $query,
                'application/json'
            )->post(
                $this->base_url . '/fapi/v1/order'
            )->collect()->toArray();


            if (
                !isset($create_order['orderId']) ||
                !isset($create_order['symbol'])
            ) {

                usleep(100000);

                ErrorLog::create([
                    'title' => 'Can\'t create order!!! Query is: ' . $query . '. Tries: ' . $i,
                    'message' => json_encode($create_order),
                ]);

                $this->telegram->send(
                    'Can\'t create order!!! Query is: ' . $query . '. Tries: ' . $i . '. JSON: ' . json_encode($create_order) . "\n"
                );

            } else
                return $create_order;

        }

        return false;

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

    public function getAllOpenOrders($symbol): array|bool
    {

        $timestamp = $this->getTimestamp();

        $query = http_build_query([
            'timestamp' => $timestamp,
            'symbol' => $symbol,
        ]);

        $open_orders = Http::withHeaders([
            'X-MBX-APIKEY' => $this->public_api,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->get($this->base_url . '/fapi/v1/openOrders', [
            'timestamp' => $timestamp,
            'symbol' => $symbol,
            'signature' => $this->generateSignatureWithQuery($query)
        ])->collect()->toArray();

        if ((is_array($open_orders) && isset($open_orders[0]['orderId']) && isset($open_orders[0]['symbol'])) || empty($open_orders))
            return $open_orders;

        return json_encode($open_orders);

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
