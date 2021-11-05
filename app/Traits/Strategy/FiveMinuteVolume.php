<?php

namespace App\Traits\Strategy;

trait FiveMinuteVolume
{

    public static function fiveMinuteVolume($candles, $increase = 10)
    {

        $all = count($candles);

        if ($all >= 5) {

            $first = array_shift($candles);

            $second = array_shift($candles);

            $volume_average = array_sum(array_column($candles, 'volume')) / count($candles);

            if ($first['volume'] >= $increase * $volume_average) return 'volume increases right now';

            if ($second['volume'] >= $increase * $volume_average) return 'volume increases now';

        }

        return false;

    }

}
