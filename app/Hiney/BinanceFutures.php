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
    public function cancelOrder($order_id, $symbol): array|bool
    {

        for ($i = 0; $i < 5; $i++) {

            $query = http_build_query([
                'timestamp' => $this->getTimestamp(),
                'orderId' => $order_id,
                'symbol' => $symbol
            ]);

            $cancel_order = Http::withHeaders([
                'X-MBX-APIKEY' => $this->public_api,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->withBody(
                $query . '&signature=' . $this->generateSignatureWithQuery($query),
                'application/json'
            )->delete(
                $this->base_url . '/fapi/v1/order'
            )->collect()->toArray();

            if (
                !isset($cancel_order['orderId']) ||
                !isset($cancel_order['symbol']) ||
                !isset($cancel_order['status'])
            ) {

                usleep(100000);

                ErrorLog::create([
                    'title' => 'Can\'t cancel order!!! Query is: ' . $query . '. Tries: ' . $i,
                    'message' => json_encode($cancel_order),
                ]);

                $this->telegram->send(
                    'Can\'t cancel order!!! Query is: ' . $query . '. Tries: ' . $i . '. JSON: ' . json_encode($cancel_order) . "\n"
                );

            } else
                return $cancel_order;

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
    public function getOrderStatus($order_id, $symbol): array|bool
    {

        for ($i = 0; $i < 5; $i++) {

            $query = http_build_query([
                'timestamp' => $this->getTimestamp(),
                'orderId' => $order_id,
                'symbol' => $symbol
            ]);

            $order_status = Http::withHeaders([
                'X-MBX-APIKEY' => $this->public_api,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->get(
                $this->base_url . '/fapi/v1/order',
                $query . '&signature=' . $this->generateSignatureWithQuery($query)
            )->collect()->toArray();

            if (
                !isset($order_status['orderId']) ||
                !isset($order_status['symbol']) ||
                !isset($order_status['status'])
            ) {

                usleep(100000);

                ErrorLog::create([
                    'title' => 'Can\'t get order status!!! Query is: ' . $query . '. Tries: ' . $i,
                    'message' => json_encode($order_status),
                ]);

                $this->telegram->send(
                    'Can\'t get order status!!! Query is: ' . $query . '. Tries: ' . $i . '. JSON: ' . json_encode($order_status) . "\n"
                );

            } else
                return $order_status;

        }

        return false;

    }

    public function getAllOpenOrders($symbol): array|bool
    {

        for ($i = 0; $i < 5; $i++) {

            $query = http_build_query([
                'timestamp' => $this->getTimestamp(),
                'symbol' => $symbol,
            ]);

            $open_orders = Http::withHeaders([
                'X-MBX-APIKEY' => $this->public_api,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->get(
                $this->base_url . '/fapi/v1/openOrders',
                $query . '&signature=' . $this->generateSignatureWithQuery($query)
            )->collect()->toArray();

            if (
                isset($open_orders['code']) ||
                isset($open_orders['msg'])
            ) {

                usleep(100000);

                ErrorLog::create([
                    'title' => 'Can\'t get all open order for pair:' . $symbol . '. Query is: ' . $query . '. Tries: ' . $i,
                    'message' => json_encode($open_orders),
                ]);

                $this->telegram->send(
                    'Can\'t get all open order for pair:' . $symbol . '. Query is: ' . $query . '. Tries: ' . $i . '. JSON: ' . json_encode($open_orders) . "\n"
                );

            } else
                return $open_orders;

        }

        return false;

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
    public function getPositionInformation($symbol = null): array|bool
    {
        for ($i = 0; $i < 5; $i++) {

            $query = http_build_query([
                'timestamp' => $this->getTimestamp()
            ]);

            if (!empty($symbol))
                $query .= '&symbol=' . $symbol;

            $positions = Http::withHeaders([
                'X-MBX-APIKEY' => $this->public_api,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->get(
                $this->base_url . '/fapi/v2/positionRisk',
                $query . '&signature=' . $this->generateSignatureWithQuery($query)
            )->collect()->toArray();

            if (
                !isset($positions[0]['symbol']) ||
                !isset($positions[0]['notional'])
            ) {

                usleep(100000);

                ErrorLog::create([
                    'title' => 'Can\'t get position information!!! Query is: ' . $query . '. Tries: ' . $i,
                    'message' => json_encode($positions),
                ]);

                $this->telegram->send(
                    'Can\'t get position information!!! Query is: ' . $query . '. Tries: ' . $i . '. JSON: ' . json_encode($positions) . "\n"
                );

            } else
                return $positions;

        }

        return false;

    }

    #[Pure] private function generateSignature(): bool|string
    {

        return hash_hmac('sha256', 'timestamp=' . $this->getTimestamp(), $this->private_api);

    }

    private function generateSignatureWithQuery($query): string
    {

        return hash_hmac('sha256', $query, $this->private_api);

    }

    private function getTimestamp(): string
    {

        list($msec, $sec) = explode(' ', microtime());

        return $sec . substr($msec, 2, 3);

    }

}
