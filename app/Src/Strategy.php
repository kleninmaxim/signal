<?php

namespace App\Src;

use App\Models\Strategy as S;
use App\Models\StrategyDefaultOption;

use App\Traits\Strategy\CoraWaveStrategy;
use App\Traits\Strategy\FinalStrategy;
use App\Traits\Strategy\FiveMinuteVolumeStrategy;
use App\Traits\Strategy\MovingAverageStrategy;

class Strategy
{

    use CoraWaveStrategy, MovingAverageStrategy, FiveMinuteVolumeStrategy;

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

}
