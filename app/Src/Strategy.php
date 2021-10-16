<?php

namespace App\Src;

use App\Models\Strategy as S;
use App\Models\StrategyDefaultOption;

class Strategy
{

    /*
     * INPUT: Все свечи приходят в таком массиве,
     * в возрастающем порядке,
     * в последовательном порядке ключа 0, 1, 2, 3 и т. д.
     *
     * OUTPUT: Возвращает массив согласно стратегии
        Array
        (
            [0] => Array
                (
                    [open] => 4689.89
                    [close] => 4378.51
                    [high] => 4939.19
                    [low] => 2817
                    [volume] => 27634.18912
                    [time_start] => 2017-09-01 03:00:00
                )

            [1] => Array
                (
                    [open] => 4378.49
                    [close] => 6463
                    [high] => 6498.01
                    [low] => 4110
                    [volume] => 41626.388463
                    [time_start] => 2017-10-01 03:00:00
                )
        )
    */

    // Получает опции для каждой стратегии
    public static function getOptions($exchange, $strategy_name, $timeframe)
    {

        return json_decode(
            StrategyDefaultOption::where([
                ['exchange', $exchange],
                ['strategy_id', S::where('name', $strategy_name)->first()->id],
                ['timeframe', $timeframe],
            ])->first()->options,
            true
        );

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

            if (isset($previous_signal)) {

                if ($previous_signal != $signal) {

                    $actions[] = [
                        'date' => $candle['time_start'],
                        'close' => $candle['close'],
                        'signal' => $signal
                    ];

                }

            }

            $previous_signal = $signal;
            $previous_candle = $candle;

        }

        array_shift($actions);

        return $actions;

    }

}
