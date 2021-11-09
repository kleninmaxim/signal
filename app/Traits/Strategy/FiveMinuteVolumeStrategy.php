<?php

namespace App\Traits\Strategy;

trait FiveMinuteVolumeStrategy
{

    public static function fiveMinuteVolume($candles, $interval, $increase = 10)
    {

        $all = count($candles);

        if ($all >= 5) {

            $first = array_shift($candles);

            $second = array_shift($candles);

            $volume_average = array_sum(array_column($candles, 'volume')) / count($candles);

            if ($first['volume'] >= $increase * $volume_average)
                return 'volume increases right now more ' . $increase . "\n" . 'volume ' . $interval . 'h: ' . round($volume_average) . "\n" . 'volume 5 min: ' . $first['volume'];

            if ($second['volume'] >= $increase * $volume_average)
                return 'volume increases now more ' . $increase . "\n" . 'volume ' . $interval . 'h: ' . round($volume_average) . "\n" . 'volume 5 min: ' . $second['volume'];

        }

        return false;

    }

}
