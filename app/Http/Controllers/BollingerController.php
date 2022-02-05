<?php

namespace App\Http\Controllers;

use \App\Src\Binance;
use App\Hiney\Strategies\Bollinger;
use App\Src\Math;

class BollingerController extends Controller
{

    public function test()
    {

        //434929 - 5 минутных свечей
        //38883 - 1 часовых свечей

        $strategy = (new Bollinger(
            array_values((new Binance())->getCandles('BTC/USDT', '1h')),
            13,
            3
        ))->run();

        $candles = array_reverse(
            array_values(
                array_filter($strategy, function ($candle) {
                    return !empty($candle['bollinger_basic']) && !empty($candle['bollinger_upper']) && !empty($candle['bollinger_lower']) && $candle['time_start'] >= '2021-01-01 06:00:00';
                })
            )
        );

        //debug($candles, true);

        $position = [];

        $profits = [];

        $prepare = '';

        foreach ($candles as $candle) {

            if (empty($position)) {

                if (empty($prepare)) {

                    if ($candle['close'] <= $candle['bollinger_lower']) {
                        $prepare = 'long';
                    } elseif ($candle['close'] >= $candle['bollinger_upper']) {
                        $prepare = 'short';
                    }

                } else {

                    if ($prepare == 'long' && $candle['close'] >= $candle['bollinger_lower']) {

                        $position = [
                            'position' => 'long',
                            'price' => $candle['close'],
                            'time_start' => $candle['time_start'],
                        ];

                        $prepare = '';

                    } elseif($prepare == 'short' && $candle['close'] <= $candle['bollinger_upper']) {

                        $position = [
                            'position' => 'short',
                            'price' => $candle['close'],
                            'time_start' => $candle['time_start'],
                        ];

                        $prepare = '';

                    }

                }

            } else {

                if (empty($prepare)) {

                    if ($position['position'] == 'short' && $candle['close'] <= $candle['bollinger_lower']) {

                        $prepare = 'long';

                    } elseif($position['position'] == 'long' && $candle['close'] >= $candle['bollinger_upper']) {


                        $prepare = 'short';

                    }

                } else {

                    if ($prepare == 'long' && $candle['close'] >= $candle['bollinger_lower']) {

                        $profits[] = [
                            'position' => $position['position'],
                            'time_start' => $position['time_start'],
                            'time_exit' => $candle['time_start'],
                            'profit' => ($position['price'] - $candle['close']) / $position['price'] * 100,
                        ];

                        $position = [
                            'position' => 'long',
                            'price' => $candle['close'],
                            'time_start' => $candle['time_start'],
                        ];

                        $prepare = '';

                    } elseif ($prepare == 'short' && $candle['close'] <= $candle['bollinger_upper']) {

                        $profits[] = [
                            'position' => $position['position'],
                            'time_start' => $position['time_start'],
                            'time_exit' => $candle['time_start'],
                            'profit' => ($candle['close'] - $position['price']) / $position['price'] * 100,
                        ];

                        $position = [
                            'position' => 'short',
                            'price' => $candle['close'],
                            'time_start' => $candle['time_start'],
                        ];

                        $prepare = '';

                    }

                }

            }

        }

        //debug($profits, true);

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
        debug($n);
        debug($p);

        debug(Math::change($p, $p + $n, 4) * 100);
        debug('Fixed. Capital start: ' . 1 . ' . Capital end: ' . Math::round(1 + array_sum($profit) / 100));
        debug('Percent. Capital start: ' . 1 . ' . Capital end: ' . Math::round($sum));

        debug($profit);

    }

}
