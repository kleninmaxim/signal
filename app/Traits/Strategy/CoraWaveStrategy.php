<?php

namespace App\Traits\Strategy;

use App\Src\Indicator;

trait CoraWaveStrategy
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
    public static function coraWaveQuick($candles, $length)
    {

        Indicator::process(
            $candles,
            ['cora_wave' => Indicator::coraWave($candles, $length)]
        ); // Добавляет в каждую свечу дополнительные значения индикатора

        return self::getActionsCoraWaveQuick($candles); // Возвращает массив с сигналами

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
    public static function coraWaveSimple($candles, $length)
    {

        Indicator::process(
            $candles,
            ['cora_wave' => Indicator::coraWave($candles, $length)]
        ); // Добавляет в каждую свечу дополнительные значения индикатора

        return self::getActionsCoraWaveSimple($candles); // Возвращает массив с сигналами

    }

    private static function getActionsCoraWaveSimple($candles)
    {

        $previous_candle = array_shift($candles);

        $actions = [];

        foreach ($candles as $candle) {

            $signal = ($previous_candle['cora_wave'] <= $candle['cora_wave']) ? 'long' : 'short';

            if (isset($previous_signal) && $previous_signal != $signal) {

                $actions[] = [
                    'date' => $candle['time_start'],
                    'close' => $candle['close'],
                    'signal' => $signal
                ];

            }

            $previous_signal = $signal;
            $previous_candle = $candle;

        }

        array_shift($actions);

        return $actions;

    }

    private static function getActionsCoraWaveQuick($candles)
    {

        $previous_candle = array_shift($candles);

        $actions = [];

        foreach ($candles as $candle) {

            $signal = ($previous_candle['cora_wave'] <= $candle['cora_wave']) ? 'long' : 'short';

            if (isset($sell) && $sell == true) {

                $actions[] = [
                    'date' => $candle['time_start'],
                    'close' => $candle['close'],
                    'signal' => 'short'
                ];

                $sell = false;

            } elseif(isset($previous_signal) && $previous_signal == 'short' && $signal == 'long') {

                $actions[] = [
                    'date' => $candle['time_start'],
                    'close' => $candle['close'],
                    'signal' => $signal
                ];

                $sell = true;

            }

            $previous_signal = $signal;
            $previous_candle = $candle;

        }

        return $actions;

    }

}
