<?php

namespace App\Traits\Strategy;

use App\Src\Math;

trait FivePercentageStrategy
{

    public static function FivePercentage($candles, $profit = 5)
    {

        $output = [];

        foreach ($candles as $candle) {

            $high = Math::percentage($candle['open'], $candle['high']);

            if ($high >= $profit) {

                $price = $candle['open'] * (1 + $profit / 100);

/*                $output[] = [
                    'open' => $candle['open'],
                    'profit' => Math::round(
                        ($candle['close'] - $price) / $price * 100
                    ),
                    'date' => $candle['time_start'],
                ];*/

                $output[] = Math::round(
                    ($candle['close'] - $price) / $price * 100
                );

            }

        }

        //sort($output);

/*        usort($output, function( $a, $b ) {
            return ($a['profit']-$b['profit']);
        });*/

        return $output;

    }

}
