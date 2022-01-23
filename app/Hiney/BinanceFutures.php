<?php

namespace App\Hiney;

use App\Hiney\Src\Math;
use App\Hiney\Src\Telegram;
use App\Models\ErrorLog;
use Carbon\Carbon;
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

        $this->telegram = new Telegram(false);

    }

    public function getContracts(): array
    {

        return Http::get($this->base_url . '/fapi/v1/exchangeInfo')->collect()->toArray();

    }

    public function getCandles($pair, $timeframe, $limit = 100, $removeCurrent = false): array
    {

        if ($candles_api = $this->getCandlesApi($pair, $timeframe, $limit)) {

            foreach ($candles_api as $key => $candle)
                $candles[$key] = [
                    'open' => $candle[1],
                    'high' => $candle[2],
                    'low' => $candle[3],
                    'close' => $candle[4],
                    'volume' => $candle[5],
                    'time_start' => Carbon::createFromTimestamp($candle[0] / 1000)->toDateTimeString()
                ];

            if ($removeCurrent) {

                $current_candle = array_pop($candles);

                if ($current_candle['time_start'] < $this->maxCandleTimeStart($timeframe))
                    $candles[] = $current_candle;

            }

        }

        return array_values($candles ?? []);

    }

    public function getBalances(): array|bool
    {

        for ($i = 0; $i < 5; $i++) {

            $query = http_build_query([
                'timestamp' => $this->getTimestamp()
            ]);

            $balances = Http::withHeaders([
                'X-MBX-APIKEY' => $this->public_api,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->get(
                $this->base_url . '/fapi/v1/account',
                $query . '&signature=' . $this->generateSignature($query)
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
    LIMIT
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

    MARKET
    Array
    (
        [orderId] => 41541317702
        [symbol] => BTCUSDT
        [status] => NEW
        [clientOrderId] => 4mEbmHrbztcDpPhAiPEjtv
        [price] => 0
        [avgPrice] => 0.00000
        [origQty] => 0.001
        [executedQty] => 0
        [cumQty] => 0
        [cumQuote] => 0
        [timeInForce] => GTC
        [type] => MARKET
        [reduceOnly] =>
        [closePosition] =>
        [side] => BUY
        [positionSide] => BOTH
        [stopPrice] => 0
        [workingType] => CONTRACT_PRICE
        [priceProtect] =>
        [origType] => MARKET
        [updateTime] => 1642938360997
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

            if (isset($options['reduce_only']))
                $query .= '&reduceOnly=' . $options['reduce_only'];

            if (isset($options['working_type']))
                $query .= '&workingType=' . $options['working_type'];

            $query .= '&signature=' . $this->generateSignature($query);

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
    public function cancelOrder(string $order_id, string $symbol): array|bool
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
                $query . '&signature=' . $this->generateSignature($query),
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
    public function getOrderStatus(string $order_id, string $symbol): array|bool
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
                $query . '&signature=' . $this->generateSignature($query)
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

    public function getAllOpenOrders(string $symbol): array|bool
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
                $query . '&signature=' . $this->generateSignature($query)
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
    public function getPositionInformation(string $symbol = null): array|bool
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
                $query . '&signature=' . $this->generateSignature($query)
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

    /*
    Array
    (
        [symbol] => TRXUSDT
        [leverage] => 50
        [maxNotionalValue] => 50000
    )
    */
    public function changeInitialLeverage($symbol, $leverage): bool|array
    {

        for ($i = 0; $i < 5; $i++) {

            $query = http_build_query([
                'timestamp' => $this->getTimestamp(),
                'symbol' => $symbol,
                'leverage' => $leverage,
            ]);

            $query .= '&signature=' . $this->generateSignature($query);

            $changed_leverage = Http::withHeaders([
                'X-MBX-APIKEY' => $this->public_api,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->withBody(
                $query,
                'application/json'
            )->post(
                $this->base_url . '/fapi/v1/leverage'
            )->collect()->toArray();

            if (
                !isset($changed_leverage['leverage']) ||
                !isset($changed_leverage['symbol'])
            ) {

                usleep(100000);

                ErrorLog::create([
                    'title' => 'Can\'t get position information!!! Query is: ' . $query . '. Tries: ' . $i,
                    'message' => json_encode($changed_leverage),
                ]);

                $this->telegram->send(
                    'Can\'t change leverage!!! Query is: ' . $query . '. Tries: ' . $i . '. JSON: ' . json_encode($changed_leverage) . "\n"
                );

            } else
                return $changed_leverage;

        }

        return false;

    }

    public function getPairsNotInPosition($balances): array
    {

        foreach ($balances['positions'] as $balance)
            if ($balance['notional'] == 0) $pairs_not_in_position[] = $balance['symbol'];

        return $pairs_not_in_position ?? [];

    }

    #[Pure] public function protectBalance(
        $balances,
        $unrealized_profit_draw_down = -15,
        $available_balance_draw_down = 10
    ) :bool
    {

        $unrealized_profit_to_wallet_balance = Math::percentage($balances['totalUnrealizedProfit'], $balances['totalWalletBalance']);

        $available_balance_to_wallet_balance = Math::percentage($balances['availableBalance'], $balances['totalWalletBalance']);

        if (
            $unrealized_profit_to_wallet_balance >= $unrealized_profit_draw_down &&
            $available_balance_to_wallet_balance >= $available_balance_draw_down
        ) return true;


        return false;

    }

    private function generateSignature($query): string
    {

        return hash_hmac('sha256', $query, $this->private_api);

    }

    private function getTimestamp(): string
    {

        list($msec, $sec) = explode(' ', microtime());

        return $sec . substr($msec, 2, 3);

    }

    private function maxCandleTimeStart($timeframe): string
    {

        return date(
            'Y-m-d H:i:s',
            strtotime(date('Y-m-d H:i:s')) - $this->timeframeInSeconds($timeframe)
        );

    }

    private function timeframeInSeconds($timeframe): int
    {

        $timeframes = [
            '1m' => 60,
            '5m' => 5 * 60,
            '15m' => 15 * 60,
            '30m' => 30 * 60,
            '1h' => 60 * 60,
            '4h' => 4 * 60 * 60,
            '1d' => 24 * 60 * 60,
            '1w' => 7 * 24 * 60 * 60,
            '1M' => 30 * 24 * 60 * 60
        ];

        return $timeframes[$timeframe] ?? 0;

    }

    private function getCandlesApi($pair, $timeframe, $limit): array|bool
    {

        for ($i = 0; $i < 5; $i++) {

            $candles = Http::get(
                $this->base_url . '/fapi/v1/klines',
                [
                    'symbol' => $pair,
                    'interval' => $timeframe,
                    'limit' => $limit,
                ]
            )->collect()->toArray();

            if (
                !isset($candles[0][0]) ||
                !isset($candles[0][1]) ||
                !isset($candles[0][2]) ||
                !isset($candles[0][3]) ||
                !isset($candles[0][4]) ||
                !isset($candles[0][5])
            ) {

                usleep(100000);

                ErrorLog::create([
                    'title' => 'Can\'t get candles throw api. Tries: ' . $i,
                    'message' => json_encode($candles),
                ]);

                (new Telegram(false))->send(
                    'Pair: ' . $pair . '. Can\'t get candles throw api!!! Tries: ' . $i . '. JSON: ' . json_encode($candles) . "\n"
                );

            } else
                return $candles;

        }

        return false;

    }

}
