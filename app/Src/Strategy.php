<?php

namespace App\Src;

class Strategy
{

    private static $short = 'SHORT';
    private static $long = 'LONG';
    private static $nothing = 'NOTHING';

    public static function macd($candles, $fast_length_ema, $slow_length_ema, $signal_length = 9)
    {
        
        if (count($candles) > $slow_length_ema + 2) {
            
            $fast_ema = self::ema($candles, $fast_length_ema);
            
            $slow_ema = self::ema($candles, $slow_length_ema);

            for ($i = $slow_length_ema; $i < count($candles); $i++) {

                $macd[]['close'] = $fast_ema[$i] - $slow_ema[$i];

            }

            $signal = self::ema($macd, $signal_length);

            for ($i = 0; $i < count($macd); $i++) {

                $hist[] = $macd[$i]['close'] - $signal[$i];

            }

            return $hist ?? [];
            
        }

        return [];

    }

    public static function proccessMacd($hist, $after = false)
    {

        if (empty($hist)) return self::$nothing;

        $last = array_pop($hist);

        $pre_last = array_pop($hist);

        if ($after) {

            $last = $pre_last;

            $pre_last = array_pop($hist);

        }

        if ($last <= 0 && $pre_last >= 0) {
            return self::$short;
        } elseif ($last >= 0 && $pre_last <= 0) {
            return self::$long;
        } else {
            return self::$nothing;
        }

    }

    public static function ema($candles, $length)
    {

        $ema = [];

        $i = 0;

        foreach ($candles as $candle) {

            if ($i == $length - 1) {

                $sum = 0;

                $j = 0;

                foreach ($candles as $candle_len) {

                    $sum += $candle_len['close'];

                    $j++;

                    if ($j >= $length) break;

                }

                $ema[] = $sum / $length;

            } elseif ($i > $length - 1) {

                $ema[] = 2 / ($length + 1) * $candle['close'] + (1 - 2 / ($length + 1)) * $ema[$i - 1];

            } else {

                $ema[] = 0;

            }

            $i++;

        }

        return $ema;
        
    }

}
