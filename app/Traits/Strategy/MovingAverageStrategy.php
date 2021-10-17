<?php

namespace App\Traits\Strategy;

use App\Src\Indicator;

trait MovingAverageStrategy
{

    /*
     * OUTPUT: Возвращает массив с простыми сигналами
        Array
        (
            [0] => Array
                (
                    [date] => 2018-11-01 03:00:00
                    [close] => 4041.32
                    [signal] => short
                )

            [1] => Array
                (
                    [date] => 2019-04-01 03:00:00
                    [close] => 5320.81
                    [signal] => long
                )
        )
    */
    public static function emaSimple($candles, $length)
    {

        Indicator::process(
            $candles,
            ['ema' => Indicator::ema($candles, $length)]
        ); // Добавляет в каждую свечу дополнительные значения индикатора

        return self::getActionsMovingSimple($candles, 'ema');

    }

    /*
     * OUTPUT: Возвращает массив с простыми сигналами
        Array
        (
            [0] => Array
                (
                    [date] => 2018-11-01 03:00:00
                    [close] => 4041.32
                    [signal] => short
                )

            [1] => Array
                (
                    [date] => 2019-04-01 03:00:00
                    [close] => 5320.81
                    [signal] => long
                )
        )
    */
    public static function wmaSimple($candles, $length)
    {

        Indicator::process(
            $candles,
            ['wma' => Indicator::wma($candles, $length)]
        ); // Добавляет в каждую свечу дополнительные значения индикатора

        return self::getActionsMovingSimple($candles, 'wma');

    }

    private static function getActionsMovingSimple($candles, $name)
    {

        $candles = array_values(
            array_filter($candles, function ($v) use ($name) {
                return $v[$name] != 0;
            })
        );

        $actions = [];

        foreach ($candles as $candle) {

            $signal = ($candle[$name] <= $candle['close']) ? 'long' : 'short';

            if (isset($previous_signal) && $previous_signal != $signal) {

                $actions[] = [
                    'date' => $candle['time_start'],
                    'close' => $candle['close'],
                    'signal' => $signal
                ];

            }

            $previous_signal = $signal;

        }

        return $actions;

    }

}
