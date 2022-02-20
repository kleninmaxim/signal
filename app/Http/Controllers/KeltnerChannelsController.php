<?php

namespace App\Http\Controllers;

use App\Hiney\BinanceFutures;
use App\Hiney\Src\Telegram;
use App\Hiney\Strategies\KeltnerChannels;
use App\Src\Binance;
use App\Src\Math;
use Illuminate\Support\Facades\Storage;

class KeltnerChannelsController extends Controller
{

    private string $disk = 'local';

    private string $file = 'precisions.json';

    public function keltnerStrategyNotification()
    {

        // пара
        $pair = 'BTCUSDT';

        // таймфрейм
        $timeframe = '1h';

        // создание телеграм для отправки сообщения
        $telegram = new Telegram(true);

        // объект для взаимодействия с фьючерсами binance через API
        $binance_futures = new BinanceFutures();

        if (
            $strategy = (
                new KeltnerChannels(
                    $binance_futures->getCandles($pair, $timeframe, 1000, true),
                    22,
                    3,
                    'ATR',
                    32
                )
            )->run()
        ) {

            // отправляет сообщение в телеграм о нашей позиции
            $telegram->send(
                '*Keltner*' . "\n" .
                '*' . $pair . '*' . "\n" .
                'Position: *' . $strategy['position'] . "\n" .
                'Price cross: *' . $strategy['price'] . '*' . "\n"
            );

        }

        return true;

    }

    public function keltnerStrategy()
    {

        // пара
        $pair = 'BTCUSDT';

        // таймфрейм
        $timeframe = '1h';

        // записать precisions в файл
        $this->saveToFileContractsPrecisions();

        // проверка на то, что файл этот есть
        if (Storage::disk($this->disk)->exists($this->file)) {


            // создание телеграм для отправки сообщения
            $telegram = new Telegram();

            // объект для взаимодействия с фьючерсами binance через API
            $binance_futures = new BinanceFutures();

            if (
                $strategy = (
                    new KeltnerChannels(
                        $binance_futures->getCandles($pair, $timeframe, 1000, true),
                        22,
                        3,
                        'ATR',
                        32
                    )
                )->run()
            ) {

                // взять все пары к которым есть информация
                $precisions = json_decode(Storage::get($this->file), true);

                // если существует precisions на пару
                if (isset($precisions[$pair])) {

                    // взять информацию о текущей позиции
                    if ($position = $binance_futures->getPositionInformation($pair)[0]) {

                        // берем текущий баланс
                        if ($balances = $binance_futures->getBalances()) {


                            // если позиция не открыта
                            if ($position['positionAmt'] == 0) {

                                // взять открытые ордера
                                if ($open_orders = $binance_futures->getAllOpenOrders($pair)) {

                                    // проверить, что позиция открыта именно на $pair

                                } else {

                                    // открыть позицию
                                    if ($binance_futures->changeInitialLeverage($pair, 10)) {

                                        // если ордер поставился
                                        if (
                                            $order = $binance_futures->createOrder(
                                                $pair,
                                                $strategy['position'],
                                                'STOP_MARKET',
                                                $balances['availableBalance'] * 0.1,
                                                $strategy['price']
                                            )
                                        ) {

                                        } else {
                                            // сообщение об ошибке
                                        }

                                    }

                                }

                            } elseif ($position['positionAmt'] < 0 && $strategy['position'] == 'buy') {

                                // закрыть открытые ордера по стоп маркету
                                $binance_futures->createOrder(
                                    $pair,
                                    $strategy['position'],
                                    'MARKET',
                                    abs($position['positionAmt']),
                                    options: ['reduce_only' => 'true']
                                );

                                // закрыть и открыть позицию
                                if ($binance_futures->changeInitialLeverage($pair, 10)) {

                                    // если ордер поставился
                                    if (
                                        $order = $binance_futures->createOrder(
                                            $pair,
                                            $strategy['position'],
                                            'STOP_MARKET',
                                            $balances['availableBalance'] * 0.1,
                                            $strategy['price']
                                        )
                                    ) {

                                    } else {
                                        // сообщение об ошибке
                                    }

                                }

                            } elseif ($position['positionAmt'] > 0 && $strategy['position'] != 'sell') {

                                // закрыть открытые ордера по стоп маркету
                                $binance_futures->createOrder(
                                    $pair,
                                    $strategy['position'],
                                    'MARKET',
                                    abs($position['positionAmt']),
                                    options: ['reduce_only' => 'true']
                                );

                                // закрыть и открыть позицию
                                if ($binance_futures->changeInitialLeverage($pair, 10)) {

                                    // если ордер поставился
                                    if (
                                        $order = $binance_futures->createOrder(
                                            $pair,
                                            $strategy['position'],
                                            'STOP_MARKET',
                                            $balances['availableBalance'] * 0.1,
                                            $strategy['price']
                                        )
                                    ) {

                                    } else {
                                        // сообщение об ошибке
                                    }

                                }

                            }

                        }

                    } else {
                        // сообщение об ошибке
                    }

                } else {
                    // сообщение об ошибке
                }

            }

        }

        return true;

    }

    public function test()
    {

        debug('Since 2021-01-01');

        $strategy = (new KeltnerChannels(
            array_values((new Binance())->getCandles('BTC/USDT', '1h')),
            22,
            3,
            'ATR',
            32
        ))->test();

/*        $strategy = (new KeltnerChannels(
            array_values((new Binance())->getCandles('BTC/USDT', '1h')),
            8,
            2,
            'R'
        ))->run();*/

        $candles = array_reverse(
            array_values(
                array_filter($strategy, function ($candle) {
                    return $candle['time_start'] >= '2021-01-01 06:00:00' && !empty($candle['keltner_channel_basic']);
                })
            )
        );

        //debug($candles, true);

        $positions = [];

        $prepare = [];

        foreach ($candles as $candle) {

            if (empty($prepare)) {

                if ($candle['keltner_channel_upper'] <= $candle['close']) {
                    $prepare = ['position' => 'long', 'price' => $candle['high']];
                } elseif ($candle['keltner_channel_lower'] >= $candle['close']) {
                    $prepare = ['position' => 'short', 'price' => $candle['low']];
                }

            } else {

                if ($candle['keltner_channel_upper'] <= $candle['close'] && $prepare['position'] == 'short') {
                    $prepare = ['position' => 'long', 'price' => $candle['high']];
                    continue;
                } elseif ($candle['keltner_channel_lower'] >= $candle['close'] && $prepare['position'] == 'long') {
                    $prepare = ['position' => 'short', 'price' => $candle['low']];
                    continue;
                }

                if ($prepare['position'] == 'short') {

                    if ($prepare['price'] >= $candle['low']) {

                        $positions[] = [
                            'position' => $prepare['position'],
                            'price' => $prepare['price'],
                            'time_start' => $candle['time_start'],
                        ];

                        $prepare = [];

                    }

                } elseif ($prepare['position'] == 'long') {

                    if ($prepare['price'] <= $candle['high']) {

                        $positions[] = [
                            'position' => $prepare['position'],
                            'price' => $prepare['price'],
                            'time_start' => $candle['time_start'],
                        ];

                        $prepare = [];

                    }

                }

            }

        }

        foreach ($positions as $key => $position) {

            if (isset($current) && $current == $position['position']) {
                unset($positions[$key]);
            } else
                $current= $position['position'];

        }

        $profits = [];

        foreach ($positions as $position) {

            if (isset($in_position))
                $profits[] = [
                    'position' => $in_position['position'],
                    'time_start' => $in_position['time_start'],
                    'time_exit' => $position['time_start'],
                    'profit' => ($in_position['position'] == 'long')
                        ? ($position['price'] - $in_position['price']) / $in_position['price'] * 100 - 0.2
                        : ($in_position['price'] - $position['price']) / $in_position['price'] * 100 - 0.2,
                ];

            $in_position = $position;

        }

        $profit = array_column($profits, 'profit');


        $n = 0;
        $p = 0;

        $i = 0;
        $j = 0;

        $sequence_of_negative = [];

        $sequence_of_positive = [];

        $negative = [];

        $sum = 1;

        foreach ($profit as $datum) {

            if ($datum <= 0) {

                $i++;

                $n++;

                if ($j != 0) $sequence_of_positive[] = $j;

                $j = 0;

                $negative[] = $datum;

            } else {

                if ($i != 0) $sequence_of_negative[] = $i;

                $j++;

                $p++;

                $i = 0;
            }

            $sum *= (1 + $datum / 100);

        }

        if ($j != 0) $sequence_of_positive[] = $j;

        if ($i != 0) $sequence_of_negative[] = $i;

        $sequence_of_negative = array_count_values($sequence_of_negative);
        $sequence_of_positive = array_count_values($sequence_of_positive);

        ksort($sequence_of_negative);
        ksort($sequence_of_positive);
        sort($negative);
        sort($profit);

        debug($sequence_of_negative);
        debug($sequence_of_positive);
        debug('Negative deals: ' . $n);
        debug('Positive deals: ' . $p);

        debug('Total closed trades: ' . $n + $p);

        debug('Percent profitable: ' . Math::change($p, $p + $n, 4) * 100);
        debug('(Net Profit) Percent. Capital start: ' . 1 . ' . Capital end: ' . Math::round($sum) . ' Percent: ' . Math::round($sum * 100) . ' %');
        debug('Fixed. Capital start: ' . 1 . ' . Capital end: ' . Math::round(1 + array_sum($profit) / 100) . ' Percent: ' . Math::round(100 + array_sum($profit)) . ' %');

//        foreach (array_reverse($profit) as $item) {
//
//            echo round($item, 2) . '<br>';
//
//        }

        debug($profit);

    }

    private function changePosition($position): string
    {

        return ($position == 'SELL') ? 'BUY' : 'SELL';

    }


    // сохраняет precisions в файл
    private function saveToFileContractsPrecisions()
    {

        $symbols = (new BinanceFutures())->getContracts()['symbols'];

        if (isset($symbols[0]['symbol'])) {

            foreach ($symbols as $contract)
                if ($contract['contractType'] == 'PERPETUAL' && str_contains($contract['symbol'], 'USDT'))
                    $precisions[$contract['symbol']] = [
                        'amount_precision' => $contract['quantityPrecision'],
                        'price_precision' => $contract['pricePrecision'],
                    ];

            Storage::disk($this->disk)->put($this->file, json_encode($precisions ?? []));

        }

    }

}
