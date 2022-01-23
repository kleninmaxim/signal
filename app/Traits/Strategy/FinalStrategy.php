<?php

namespace App\Traits\Strategy;

use App\Src\Indicator;
use App\Src\Math;

trait FinalStrategy
{

    public static function finalShortSimple($candles, $length, $loss = -1)
    {

        Indicator::process(
            $candles,
            ['cora_wave' => Indicator::coraWave($candles, $length)]
        ); // Добавляет в каждую свечу дополнительные значения индикатора

        $filter_candles = self::getActionsfinalSimple($candles);

        $output = [];
        $lows = [];

        foreach ($filter_candles as $filter_candle) {

            $low = Math::percentage($filter_candle['open'], $filter_candle['low']);

            if ($low < $loss) {

                $output[] = $loss;

            } else {

                $output[] = Math::percentage($filter_candle['open'], $filter_candle['close']);

            }

            $lows[] = $low;

        }

        sort($lows);

        //debug($lows);

        //debug(Math::statisticAnalyse($lows));

        return $output;

    }

    public static function finalSimple($candles, $length, $profit = 1)
    {

        Indicator::process(
            $candles,
            ['cora_wave' => Indicator::coraWave($candles, $length)]
        ); // Добавляет в каждую свечу дополнительные значения индикатора

        $filter_candles = self::getActionsfinalSimple($candles);

        $output = [];
        $highs = [];

        foreach ($filter_candles as $filter_candle) {

            $high = Math::percentage($filter_candle['open'], $filter_candle['high']);

            if ($high >= $profit) {

                $output[] = /*-1 * */$profit - 0.05;

            } else {

                $output[] = /*-1 * */Math::percentage($filter_candle['open'], $filter_candle['close']) - 0.05;

            }

            $highs[] = $high;

//            $low = -1 * Math::percentage($filter_candle['open'], $filter_candle['low']);
//
//            if ($low >= $profit) {
//
//                $output[] = -1 * $profit - 0.05;
//
//            } else {
//
//                $output[] = Math::percentage($filter_candle['open'], $filter_candle['close']) - 0.05;
//
//            }
//
//            $highs[] = $low;

        }

        sort($highs);

        //debug(Math::statisticAnalyse($highs));

        return [$output, $highs];

    }

    private static function getActionsfinalSimple($candles)
    {

        $filter_candles = [];

        foreach ($candles as $candle) {

            if ($candle['cora_wave'] == 0) {
                continue;
            } else {

                if (isset($previous_candle)) {

                    $signal = ($previous_candle['cora_wave'] <= $candle['cora_wave']) ? 'long' : 'short';

                    $previous_candle = $candle;

                    $candle['cora_wave'] = $signal;

                    $filter_candles[] = $candle;

                } else {

                    $previous_candle = array_shift($candles);

                }

            }

        }

        return $filter_candles;

    }

}
