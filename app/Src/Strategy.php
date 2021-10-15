<?php

namespace App\Src;

use App\Models\Strategy as S;
use App\Models\StrategyDefaultOption;

class Strategy
{

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

    public static function coraWave($candles, $length, $r_multi = 2, $smooth = true, $man_smooth = 1)
    {

        $candles = array_reverse($candles);

        foreach ($candles as $key => $candle) {
            $candles[$key]['hlc3'] = ($candle['high'] + $candle['low'] + $candle['close']) / 3;
        }

        $s = $smooth ? max(round(sqrt($length)), 1) : $man_smooth;

        $cora_raw = self::f_adj_crwma($candles ?? [], $length, 0.01, $r_multi);

        $cora_raw = array_reverse($cora_raw);

        return self::wma($cora_raw, $s);

    }

    public static function parabolicSar($candles, $start = 0.02, $inc = 0.02, $max = 0.2)
    {

        $candles = array_values($candles);

        $result = null;
        $maxMin = null;
        $acceleration = null;
        $isBelow = null;

        foreach ($candles as $key => $candle) {

            $isFirstTrendBar = false;

            if ($key != 0) {

                if ($key == 1) {

                    if ($candle['close'] > $candles[$key - 1]['close']) {

                        $isBelow = true;
                        $maxMin = $candle['high'];
                        $result = $candles[$key - 1]['low'];

                    } else {

                        $isBelow = false;
                        $maxMin = $candle['low'];
                        $result = $candles[$key - 1]['high'];

                    }
                    $isFirstTrendBar = true;
                    $acceleration = $start;

                }

                $result = $result + $acceleration * ($maxMin - $result);

                if ($isBelow) {

                    if ($result > $candle['low']) {

                        $isFirstTrendBar = true;
                        $isBelow = false;
                        $result = max($candle['high'], $maxMin);
                        $maxMin = $candle['low'];
                        $acceleration = $start;

                    }

                } else {

                    if ($result < $candle['high']) {

                        $isFirstTrendBar = true;
                        $isBelow = true;
                        $result = min($candle['low'], $maxMin);
                        $maxMin = $candle['high'];
                        $acceleration = $start;

                    }

                }

                if (!$isFirstTrendBar) {

                    if ($isBelow) {

                        if ($candle['high'] > $maxMin) {

                            $maxMin = $candle['high'];

                            $acceleration = min($acceleration + $inc, $max);

                        }

                    } else {

                        if ($candle['low'] < $maxMin) {

                            $maxMin = $candle['low'];

                            $acceleration = min($acceleration + $inc, $max);

                        }

                    }

                }

                if ($isBelow) {

                    $result = min($result, $candles[$key - 1]['low']);

                    if ($key != 1) $result = min($result, $candles[$key - 2]['low']);

                } else {

                    $result = max($result, $candles[$key - 1]['high']);

                    if ($key != 1) $result = max($result, $candles[$key - 2]['high']);

                }

            }

            $signal[] = $result ?? 0;

        }

        return $signal ?? [];

    }

    public static function macd($candles, $fast_length_ema, $slow_length_ema, $signal_length, $more = false)
    {

        if (count($candles) > $slow_length_ema + 2) {

            $fast_ema = self::ema($candles, $fast_length_ema);

            $slow_ema = self::ema($candles, $slow_length_ema);

            for ($i = $slow_length_ema; $i < count($candles); $i++) {

                $macd[]['close'] = $fast_ema[$i] - $slow_ema[$i];

            }

            $signal = self::ema($macd, $signal_length);

            for ($i = 0; $i < count($macd); $i++) {

                if ($more) {
                    $hist_pre['hist'] = $macd[$i]['close'] - $signal[$i];
                    $hist_pre['close'] = $candles[$i + $slow_length_ema]['close'];
                    $hist_pre['time_start'] = $candles[$i + $slow_length_ema]['time_start'];

                    $hist[] = $hist_pre;
                } else {
                    $hist[] = $macd[$i]['close'] - $signal[$i];
                }

            }

            return $hist ?? [];

        }

        return [];

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

    public static function wma($candles, $length)
    {
        $candles = array_reverse($candles);

        $all = count($candles);

        foreach ($candles as $key => $candle) {

            if ($key + $length > $all) {
                $signals[] = 0;
                continue;
            }

            $norm = 0;

            $sum = 0;

            for ($i = 0; $i <= $length - 1; $i++) {

                $weight = ($length - $i) * $length;

                $norm += $weight;

                $sum += $candles[$key + $i]['close'] * $weight;

            }

            $signals[] = $sum / $norm;

        }

        return array_reverse($signals ?? []);

    }



    private static function f_adj_crwma($sources, $length, $Start_Wt, $r_multi)
    {

        $sources = array_values($sources);

        $all = count($sources);

        foreach ($sources as $key => $source) {

            if ($key + $length > $all) {
                $signals[]['close'] = 0;
                continue;
            }

            $numerator = 0;

            $denom = 0;

            $c_weight = 0;

            $End_Wt = $length;

            $r = pow(($End_Wt / $Start_Wt), (1 / ($length - 1))) - 1;

            $base = 1 + $r * $r_multi;

            for ($i = 0; $i < $length - 1; $i++) {

                $c_weight = $Start_Wt * pow($base, ($length - $i));

                $numerator += $sources[$key + $i]['hlc3'] * $c_weight;

                $denom +=  $c_weight;

            }

            $signals[]['close'] = $numerator / $denom;

        }


        return $signals ?? [];

    }

}
