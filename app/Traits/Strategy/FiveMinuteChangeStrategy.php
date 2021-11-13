<?php

namespace App\Traits\Strategy;

trait FiveMinuteChangeStrategy
{

    public static function fiveMinuteChange($candles)
    {

        $all = count($candles);

        if ($all >= 5) {

            $first = array_shift($candles);

            $second = array_shift($candles);

            $first_change = ($first['close'] - $first['open']) / $first['open'] * 100;

            $second_change = ($second['close'] - $second['open']) / $second['open'] * 100;

            if ($first_change >= 5)
                return 'five minute change right now more ' . "\n" .
                    'change price 5 min: ' . $first_change;

            if ($second_change >= 5)
                return 'five minute change now more ' . "\n" .
                    'change price 5 min: ' . $second_change;

        }

        return false;

    }

}
