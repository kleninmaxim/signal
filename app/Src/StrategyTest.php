<?php

namespace App\Src;

use Carbon\Carbon;

use App\Src\Strategy;

use App\Models\BinancePair;
use App\Models\BinanceFiveMinuteCandle;
use App\Models\BinanceFifteenMinuteCandle;
use App\Models\BinanceThirtyMinuteCandle;
use App\Models\BinanceHourCandle;
use App\Models\BinanceDayCandle;
use App\Models\TinkoffDayCandle;
use App\Models\TinkoffTicker;
use App\Models\TinkoffFourHourCandle;
use App\Models\TinkoffHourCandle;

class StrategyTest
{

    public static function coraWave($candles, $length)
    {

        $signals = Strategy::coraWave($candles, $length);

        foreach ($signals as $key => $signal) {
            if ($signal != 0) {
                $pre['date'] = $candles[$key]['time_start'];
                $pre['signal'] = $signal;
                $pre['close'] = $candles[$key]['close'];

                $cora_waves[] = $pre;
            }
        }

        return $cora_waves ?? [];

    }

    /*
        Array (
            [0] => Array
                (
                    [date] => 2018-04-01 03:00:00
                    [action] => -5320.81
                )

            [1] => Array
                (
                    [date] => 2018-09-01 03:00:00
                    [action] => 8289.34
                )
        )
    */
    public static function proccessCoraWaveSimple($candles, $length)
    {

        $cora_waves = self::coraWave($candles, $length);

        $first = array_shift($cora_waves);

        $actions = [];

        foreach ($cora_waves as $key => $cora_wave) {

            if ($first['signal'] <= $cora_wave['signal']) $signal = 'long';
            else $signal = 'short';

            if (isset($prev)) {

                if ($prev == 'long' && $prev != $signal) {
                    $pre['date'] = $candles[$key]['time_start'];
                    $pre['action'] = $cora_wave['close'];

                    $actions[] = $pre;
                } elseif ($prev == 'short' && $prev != $signal) {
                    $pre['date'] = $candles[$key]['time_start'];
                    $pre['action'] = -$cora_wave['close'];

                    $actions[] = $pre;
                }

            }

            $prev = $signal;
            $first = $cora_wave;

        }

        if (count($actions) >= 5) {

            $first = array_shift($actions);

            if ($first['action'] <= 0) array_shift($actions);

            $last = array_pop($actions);

            if ($last['action'] >= 0) $actions[] = $last;

            return $actions;

        }

        return [];

    }

    /*
        Array (
            [0] => Array
                (
                    [date] => 2018-04-01 03:00:00
                    [action] => -5320.81
                )

            [1] => Array
                (
                    [date] => 2018-09-01 03:00:00
                    [action] => 8289.34
                )
        )
    */
    public static function capitalJustAction($datas)
    {

        $first = array_shift($datas);

        $profit_percentage_sum = 0;

        $day_sum = 0;

        foreach ($datas as $key => $data) {

            if ($data['action'] < 0) {

                $first = $data;

                continue;

            }

            $days = Carbon::parse($data['date'])->diffInDays(Carbon::parse($first['date']));

            $profit = $data['action'] + $first['action'];

            $profit_percentage = $profit / $first['action'] * (-100);

            $profit_percentage_apy = $days != 0 ? $profit_percentage * 365 / $days : 0;

            $profit_percentage_sum += $profit_percentage;

            $day_sum += $days;

            $result[$key]['buy'] = $first['action'] * -1;
            $result[$key]['sell'] = $data['action'];
            $result[$key]['profit'] = $profit;
            $result[$key]['days'] = $days;
            $result[$key]['profit_percentage'] = $profit_percentage;
            $result[$key]['profit_percentage_apy'] = $profit_percentage_apy;

        }

        $profit_percentage_apy_sum = $day_sum != 0 ? $profit_percentage_sum * 365 / $day_sum : 0;

        return [
            'profit_percentage_sum' => $profit_percentage_sum,
            'day_sum' => $day_sum,
            'profit_percentage_apy_sum' => $profit_percentage_apy_sum,
        ];

    }

}
