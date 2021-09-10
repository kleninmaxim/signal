<?php

namespace App\Src;

use App\Models\Strategy as S;
use App\Models\StrategyDefaultOption;

class Strategy
{

    private static $short = 'SHORT';
    private static $long = 'LONG';
    private static $nothing = 'NOTHING';

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

    public static function proccessMacd($candles, $fast_length_ema, $slow_length_ema, $signal_length, $after = false)
    {

        $hist = self::macd($candles, $fast_length_ema, $slow_length_ema, $signal_length);

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
