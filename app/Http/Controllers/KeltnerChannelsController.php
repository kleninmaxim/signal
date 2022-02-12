<?php

namespace App\Http\Controllers;

use App\Hiney\Strategies\KeltnerChannels;
use App\Src\Binance;
use App\Src\Math;

class KeltnerChannelsController extends Controller
{

    public function keltnerStrategy()
    {

        return true;

    }

    public function test()
    {

        $strategy = (new KeltnerChannels(
            array_values((new Binance())->getCandles('ETH/USDT', '1h')),
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
                    return /*$candle['time_start'] >= '2021-01-01 06:00:00' && */!empty($candle['keltner_channel_basic']);
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

        debug($profit);

    }

}
