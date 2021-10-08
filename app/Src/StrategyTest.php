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

            $profit_percentage_apy = $profit_percentage * $days / 365;

            $profit_percentage_sum += $profit_percentage;

            $day_sum += $days;

            $result[$key]['buy'] = $first['action'] * -1;
            $result[$key]['sell'] = $data['action'];
            $result[$key]['profit'] = $profit;
            $result[$key]['days'] = $days;
            $result[$key]['profit_percentage'] = $profit_percentage;
            $result[$key]['profit_percentage_apy'] = $profit_percentage_apy;

        }

        $profit_percentage_apy_sum = $profit_percentage_sum * $day_sum / 365;

        return [
            'profit_percentage_sum' => $profit_percentage_sum,
            'day_sum' => $day_sum,
            'profit_percentage_apy_sum' => $profit_percentage_apy_sum,
        ];

    }

    public function testStrategyBinance()
    {

        $pairs = BinancePair::all()->toArray();

        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();

        foreach ($pairs as $pair) {

            $candles = BinanceThirtyMinuteCandle::where('binance_pair_id', $pair['id'])
                ->orderBy('time_start', 'desc')->skip(0)->take(80000)->get()->toArray();

            $candles = array_reverse($candles);

            $dots = Strategy::parabolicSar($candles);
            $ema = array_filter(Strategy::ema($candles, 200));
            $hist = Strategy::macd($candles, 100, 200, 9);

            $min = min(count($dots), count($ema), count($hist), count($candles));

            $dots = array_slice($dots, count($dots) - $min);
            $ema = array_slice($ema, count($ema) - $min);
            $hist = array_slice($hist, count($hist) - $min);
            $candles = array_slice($candles, count($candles) - $min);

            $strategies = [];

            foreach ($candles as $key => $candle) {

                $strategy_pre['close'] = $candle['close'];
                $strategy_pre['high'] = $candle['high'];
                $strategy_pre['low'] = $candle['low'];
                $strategy_pre['time_start'] = $candle['time_start'];
                $strategy_pre['dots'] = $dots[$key];
                $strategy_pre['ema'] = $ema[$key];
                $strategy_pre['hist'] = $hist[$key];

                $strategies[] = $strategy_pre;

            }

            $this->proccessMacdEmaParabolic($strategies);

            $i = 0;

            $position = [];

            $percentage = 5;

            $prev = [];

            $capital = [];

            $consequences = [];

            $consequences_percentage = [];

            $sum = 0;

            foreach ($strategies as $strategy) {

                if (!empty($prev)) {

                    if ($prev['dots'] == 'short' && $strategy['dots'] == 'long') {

                        if ($strategy['ema'] == 'long' && $strategy['hist'] == 'long') {

                            if ((1 + $percentage / 100) * $strategy['close'] < $strategy['profit']) {
                                $strategy['profit'] = (1 + $percentage / 100) * $strategy['close'];

                                $strategy['stop'] = (1 - $percentage / 100) * $strategy['close'];
                            }

                            $position = $strategy;

                        }

                    }

                    if (!empty($position)) {

                        if ($strategy['low'] <= $position['stop']) {

                            $capital_pre['profit'] = $position['stop'] - $position['close'];

                            $capital_pre['profit_percentage'] = round($capital_pre['profit'] / $position['close'] * 100, 2);

                            $capital_pre['time_start'] = $position['time_start'];

                            $capital[] = $capital_pre;

                            $i++;

                            $position = [];

                        } elseif ($strategy['high'] >= $position['profit']) {

                            $capital_pre['profit'] = $position['profit'] - $position['close'];

                            $capital_pre['profit_percentage'] = round($capital_pre['profit'] / $position['close'] * 100, 2);

                            $capital_pre['time_start'] = $position['time_start'];

                            $capital[] = $capital_pre;

                            $consequences[] = $i;

                            $i = 0;

                            $position = [];

                        }

                    }

                }

                $prev = $strategy;

            }

            if (!empty($capital)) {

                foreach ($capital as $c) {

                    $sum += $c['profit_percentage'];

                    $profit_percentage[] = $c['profit_percentage'];

                }

                $realTime = Carbon::now();

                $first = array_shift($capital);

                $diff = $realTime->diffInDays($first['time_start']);

                array_unshift($capital, $first);

                debug($sum ?? []);
                debug(round($sum / $diff * 365, 2));

            }

            if (!empty($consequences)) {

                $consequences = array_count_values($consequences);

                ksort($consequences);

                foreach ($consequences as $key => $consequence) {

                    $consequences_percentage[$key] = round($consequence / array_sum($consequences) * 100, 2);

                }

                //debug($consequences);

                if (!empty($consequences_percentage)) {

                    $consequence_percentage_sum = [];

                    $consequence_percentage_sum_prev = 0;

                    foreach ($consequences_percentage as $key => $consequence_percentage) {

                        $consequence_percentage_sum_prev += $consequence_percentage;

                        $consequence_percentage_sum[$key] = $consequence_percentage_sum_prev;

                    }

                    debug($consequences_percentage);

                    debug($consequence_percentage_sum);

                }

            }

            debug($capital ?? [], true);

        }

    }

    public function macd()
    {


        $pairs = BinancePair::all()->toArray();

        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();

        foreach ($pairs as $pair) {

            $candles = BinanceHourCandle::where('binance_pair_id', $pair['id'])
                ->orderBy('time_start', 'desc')->skip(0)->take(50000)->get()->toArray();

            $candles = array_reverse($candles);

            $hist = Strategy::macd($candles, 100, 200, 9, true);

            $signals = $this->getSignalMacd($hist);

            if (!empty($signals)) {

                $i = 0;

                $consequences = [];

                $sum = 0;

                $prev = [];

                $capital = [];

                $consequences_percentage = [];

                foreach ($signals as $key => $signal) {

                    if (!empty($prev)) {

                        if ($signal['signal'] == 'long') {

                            $profit = $prev[$key - 1]['close'] - $signal['close'];

                        } elseif ($signal['signal'] == 'short') {

                            $profit = $signal['close'] - $prev[$key - 1]['close'];

                        } else throw new \Exception();

                        $capital_pre['profit_percentage'] = round($profit / $prev[$key - 1]['close'] * 100, 2);

                        $capital_pre['time_start'] = $prev[$key - 1]['time_start'];

                        $capital_pre['time_end'] = $signal['time_start'];

                        if ($signal['signal'] == 'short') {

                            $capital[] = $capital_pre;

                            if ($profit <= 0) {

                                $i++;

                            } else {

                                $consequences[] = $i;

                                $i = 0;

                            }
                        }

                    }

                    unset($prev);

                    $prev[$key] = $signal;

                }

                if (!empty($capital)) {

                    foreach ($capital as $c) {

                        $sum += $c['profit_percentage'];

                    }

                    $realTime = Carbon::now();

                    $first = array_shift($capital);

                    $diff = $realTime->diffInDays($first['time_start']);

                    array_unshift($capital, $first);

                    debug($sum);
                    debug(round($sum / $diff * 365, 2));
//                    debug($capital);

                }

                if (!empty($consequences)) {

                    $consequences = array_count_values($consequences);

                    ksort($consequences);

                    foreach ($consequences as $key => $consequence) {

                        $consequences_percentage[$key] = round($consequence / array_sum($consequences) * 100, 2);

                    }

                    // debug($consequences);

                    if (!empty($consequences_percentage)) {

                        $consequence_percentage_sum = [];

                        $consequence_percentage_sum_prev = 0;

                        foreach ($consequences_percentage as $key => $consequence_percentage) {

                            $consequence_percentage_sum_prev += $consequence_percentage;

                            $consequence_percentage_sum[$key] = $consequence_percentage_sum_prev;

                        }

                        debug($consequences_percentage);

                        debug($consequence_percentage_sum);

                    }

                }

            }

        }

    }

    public function macdEmaParabolicSar()
    {

        $pairs = BinancePair::all()->toArray();

        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();

        foreach ($pairs as $pair) {

            $candles = BinanceFifteenMinuteCandle::where('binance_pair_id', $pair['id'])
                ->orderBy('time_start', 'desc')->skip(50000)->take(50000)->get()->toArray();

            $candles = array_reverse($candles);

            $dots = Strategy::parabolicSar($candles);
            $ema = array_filter(Strategy::ema($candles, 200));
            $hist = Strategy::macd($candles, 100, 200, 9);

            $min = min(count($dots), count($ema), count($hist), count($candles));

            $dots = array_slice($dots, count($dots) - $min);
            $ema = array_slice($ema, count($ema) - $min);
            $hist = array_slice($hist, count($hist) - $min);
            $candles = array_slice($candles, count($candles) - $min);

            $strategies = [];

            foreach ($candles as $key => $candle) {

                $strategy_pre['close'] = $candle['close'];
                $strategy_pre['high'] = $candle['high'];
                $strategy_pre['low'] = $candle['low'];
                $strategy_pre['time_start'] = $candle['time_start'];
                $strategy_pre['dots'] = $dots[$key];
                $strategy_pre['ema'] = $ema[$key];
                $strategy_pre['hist'] = $hist[$key];

                $strategies[] = $strategy_pre;

            }

            $this->proccessMacdEmaParabolic($strategies);

            $i = 0;

            $position = [];

            $percentage = 2;

            $prev = [];

            $capital = [];

            $consequences = [];

            $consequences_percentage = [];

            $sum = 0;

            foreach ($strategies as $strategy) {

                if (!empty($prev)) {

                    if ($prev['dots'] == 'short' && $strategy['dots'] == 'long') {

                        if ($strategy['ema'] == 'long' && $strategy['hist'] == 'long') {

                            if ((1 + $percentage / 100) * $strategy['close'] < $strategy['profit']) {
                                $strategy['profit'] = (1 + $percentage / 100) * $strategy['close'];

                                $strategy['stop'] = (1 - $percentage / 100) * $strategy['close'];
                            }

                            $position = $strategy;

                        }

                    }

                    if (!empty($position)) {

                        if ($strategy['low'] <= $position['stop']) {

                            $capital_pre['profit'] = $position['stop'] - $position['close'];

                            $capital_pre['profit_percentage'] = round($capital_pre['profit'] / $position['close'] * 100, 2);

                            $capital_pre['time_start'] = $position['time_start'];

                            $capital[] = $capital_pre;

                            $i++;

                            $position = [];

                        } elseif ($strategy['high'] >= $position['profit']) {

                            $capital_pre['profit'] = $position['profit'] - $position['close'];

                            $capital_pre['profit_percentage'] = round($capital_pre['profit'] / $position['close'] * 100, 2);

                            $capital_pre['time_start'] = $position['time_start'];

                            $capital[] = $capital_pre;

                            $consequences[] = $i;

                            $i = 0;

                            $position = [];

                        }

                    }

                }

                $prev = $strategy;

            }

            if (!empty($capital)) {

                foreach ($capital as $c) {

                    $sum += $c['profit_percentage'];

                    $profit_percentage[] = $c['profit_percentage'];

                }

                $realTime = Carbon::now();

                $first = array_shift($capital);

                $diff = $realTime->diffInDays($first['time_start']);

                array_unshift($capital, $first);

                /*                debug($sum ?? []);
                                debug(round($sum / $diff * 365, 2));*/

            }

            if (!empty($consequences)) {

                $consequences = array_count_values($consequences);

                ksort($consequences);

                foreach ($consequences as $key => $consequence) {

                    $consequences_percentage[$key] = round($consequence / array_sum($consequences) * 100, 2);

                }

                //debug($consequences);

                if (!empty($consequences_percentage)) {

                    $consequence_percentage_sum = [];

                    $consequence_percentage_sum_prev = 0;

                    foreach ($consequences_percentage as $key => $consequence_percentage) {

                        $consequence_percentage_sum_prev += $consequence_percentage;

                        $consequence_percentage_sum[$key] = $consequence_percentage_sum_prev;

                    }

                    debug($consequences_percentage);

                    debug($consequence_percentage_sum);

                }

            }

            debug($capital ?? [], true);

        }

    }

    public function storetestStrategyBinance()
    {


        $pairs = BinancePair::all()->toArray();

        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();

        foreach ($pairs as $pair) {

            $candles = BinanceThirtyMinuteCandle::where('binance_pair_id', $pair['id'])
                ->orderBy('time_start', 'desc')->take(50000)->get()->toArray();

            $candles = array_reverse($candles);

            $dots = Strategy::parabolicSar($candles);
            $ema = array_filter(Strategy::ema($candles, 200));
            $hist = Strategy::macd($candles, 12, 26, 9);

            $min = min(count($dots), count($ema), count($hist), count($candles));

            $dots = array_slice($dots, count($dots) - $min);
            $ema = array_slice($ema, count($ema) - $min);
            $hist = array_slice($hist, count($hist) - $min);
            $candles = array_slice($candles, count($candles) - $min);

            foreach ($candles as $key => $candle) {

                $strategy_pre['close'] = $candle['close'];
                $strategy_pre['high'] = $candle['high'];
                $strategy_pre['low'] = $candle['low'];
                $strategy_pre['time_start'] = $candle['time_start'];
                $strategy_pre['dots'] = $dots[$key];
                $strategy_pre['ema'] = $ema[$key];
                $strategy_pre['hist'] = $hist[$key];

                $strategies[] = $strategy_pre;

            }

            $this->proccessMacdEmaParabolic($strategies);

            $profit = 0;

            $stop = 0;

            $i = 0;

            $percentage = 10;

            foreach ($strategies as $strategy) {

                if (isset($prev_dot)) {

                    if ($prev_dot != $strategy['dots']) {

                        if ($profit == 0 && $stop == 0) {

                            if ($strategy['dots'] == $strategy['ema'] && $strategy['dots'] == $strategy['hist']) {

                                $close = $strategy['close'];

                                if ((1 + $percentage / 100) * $close < $strategy['profit']) {
                                    $profit = (1 + $percentage / 100) * $close;

                                    $stop = (1 - $percentage / 100) * $close;
                                } else {
                                    $profit = $strategy['profit'];

                                    $stop = $strategy['stop'];
                                }

                                $time_start = $strategy['time_start'];

                                $current_dot = $strategy['dots'];

                                $position = $strategy['dots'];

                            }

                        }

                    }

                    if ($profit != 0 && $stop != 0) {

                        $capital_pre['time_start'] = $time_start;

                        $capital_pre['close'] = $close;

                        $capital_pre['position'] = $position;

                        if ($current_dot == 'long') {

                            if ($strategy['low'] <= $stop) {

                                $capital_pre['profit'] = $stop - $close;

                                $capital_pre['profit_percentage'] = round(($stop - $close) / $close * 100, 2);

                                $capital[] = $capital_pre;

                                $profit = 0;

                                $stop = 0;

                                if ($capital_pre['profit'] <= 0) {
                                    $i++;
                                } else {
                                    $conseq[] = $i;
                                    $i = 0;
                                }

                            } elseif ($strategy['high'] >= $profit) {

                                $capital_pre['profit'] = $profit - $close;

                                $capital_pre['profit_percentage'] = round(($profit - $close) / $close * 100, 2);

                                $capital[] = $capital_pre;

                                $profit = 0;

                                $stop = 0;

                                if ($capital_pre['profit'] <= 0) {
                                    $i++;
                                } else {
                                    $conseq[] = $i;
                                    $i = 0;
                                }

                            }

                        } else {

                            if ($strategy['high'] >= $stop) {

                                $capital_pre['profit'] = $close - $stop;

                                $capital_pre['profit_percentage'] = round(($close - $stop) / $close * 100, 2);

                                $capital[] = $capital_pre;

                                $profit = 0;

                                $stop = 0;

                                /*                                if ($capital_pre['profit'] <= 0) {
                                                                    $i++;
                                                                } else {
                                                                    $conseq[] = $i;
                                                                    $i = 0;
                                                                }*/

                            }

                            if ($strategy['low'] <= $profit) {

                                $capital_pre['profit'] = $close - $profit;

                                $capital_pre['profit_percentage'] = round(($close - $profit) / $close * 100, 2);

                                $capital[] = $capital_pre;

                                $profit = 0;

                                $stop = 0;

                                /*                                if ($capital_pre['profit'] <= 0) {
                                                                    $i++;
                                                                } else {
                                                                    $conseq[] = $i;
                                                                    $i = 0;
                                                                }*/

                            }

                        }

                    }

                }

                $prev_dot = $strategy['dots'];

            }

            $sum = 0;

            if (isset($capital)) {

                foreach ($capital as $key => $c) {

                    if ($c['position'] == 'short') {
                        unset($capital[$key]);
                    } else {

                        $sum += $c['profit_percentage'];

                        $profit_percentage[] = $capital[$key]['profit_percentage'];

                    }

                }

            }

            if (isset($conseq)) {

                $conseq = array_count_values($conseq);

                ksort($conseq);

                foreach ($conseq as $c) {

                    $conseq_percentage[] = round($c / array_sum($conseq) * 100, 2);

                }

            }

            $realTime = Carbon::now();

            $first = array_shift($capital);

            $diff = $realTime->diffInDays($first['time_start']);

            array_unshift($capital, $first);

            debug($sum ?? []);
            debug(round($sum / $diff * 365, 2));
            debug($conseq ?? []);
            debug($conseq_percentage ?? []);
            debug($capital ?? [], true);

        }

    }

    public function proccessMacdEmaParabolic(&$strategies)
    {

        foreach ($strategies as $key => $strategy) {

            $strategies[$key]['stop'] = $strategy['dots'];

            $strategies[$key]['profit'] = 2 * $strategy['close'] - $strategy['dots'];

            if ($strategy['close'] < $strategy['ema']) {

                $strategies[$key]['ema'] = 'short';

            } else {

                $strategies[$key]['ema'] = 'long';

            }

            if ($strategy['hist'] < 0) {

                $strategies[$key]['hist'] = 'short';

            } else {

                $strategies[$key]['hist'] = 'long';

            }

            if ($strategy['close'] < $strategy['dots']) {

                $strategies[$key]['dots'] = 'short';

            } else {

                $strategies[$key]['dots'] = 'long';

            }

        }

    }

    public function binanceMyTest()
    {

        $pairs = BinancePair::all()->toArray();

        $pairs = BinancePair::where('pair', 'DOGE/USDT')->get()->toArray();

        foreach ($pairs as $pair) {

            $candles = BinanceFiveMinuteCandle::where('binance_pair_id', $pair['id'])
                ->orderBy('time_start', 'desc')->take(50000)->get()->toArray();

            $candles = array_reverse($candles);

            $array = [];

            for ($i = 0; $i < count($candles); $i++) {

                if ($i != 0) {

                    $array[] = ($candles[$i]['close'] - $candles[$i - 1]['close']) / $candles[$i - 1]['close'];

                    /*                $plus = 0;
                                    $minus = 0;
                                    $p_c = 0;
                                    $m_c = 0;

                                    foreach ($array as $arr) {

                                        if ($arr > 0) {
                                            $plus += $arr;
                                            $p_c ++;
                                        } else {
                                            $minus += $arr;
                                            $m_c ++;
                                        }

                                    }

                                    $pre['plus'] = ($p_c == 0) ? 0 : $plus / $p_c * 100;
                                    $pre['minus'] = ($m_c == 0) ? 0 : $minus / $m_c * 100;
                                    $pre['time'] = $candles[$i]['time_start'];

                                    $all[] = $pre;*/

                }

            }

            /*        foreach ($candles as $candle) {

                        $array[] = ($candle['close'] - $candle['open']) / $candle['open'];

                    }*/

            if (!empty($array)) {

//            debug(array_sum($array) / count($array));

                $plus = 0;
                $minus = 0;
                $p_c = 0;
                $m_c = 0;

                foreach ($array as $arr) {

                    if ($arr > 0) {
                        $plus += $arr;
                        $p_c++;
                    } else {
                        $minus += $arr;
                        $m_c++;
                    }

                }

                debug($pair['pair'] . ' | ' . $plus / $p_c * 100 . ' | ' . $minus / $m_c * 100);

                $output = array_slice(array_reverse($array), 0, round(count($array) * 0.1618034));

                $plus = 0;
                $minus = 0;
                $p_c = 0;
                $m_c = 0;
                $all = [];

                foreach ($output as $arr) {

                    if ($arr > 0) {
                        $plus += $arr;
                        $p_c++;
                    } else {
                        $minus += $arr;
                        $m_c++;
                    }

                }

                $all['plus'] = ($p_c == 0) ? 0 : $plus / $p_c * 100;
                $all['minus'] = ($m_c == 0) ? 0 : $minus / $m_c * 100;

                debug($all);

//            debug($all);

            } else {

                debug('array empty');

            }

        }


    }

    public function test()
    {

        $pairs = BinancePair::all()->toArray();

        foreach ($pairs as $pair) {

            $candles = BinanceDayCandle::where('binance_pair_id', $pair['id'])
                ->orderBy('time_start', 'desc')->take(90000)->get()->toArray();

            $candles = array_reverse($candles);

            $hist = Strategy::macd($candles, 100, 200, 9, true);

            $signals = $this->getSignalMacd($hist);

            if (!empty($signals)) {

                $capital = $this->priceLongFlexibleCapital($signals, $first_price);

                $realTime = Carbon::now();

                $diff = $realTime->diffInDays($signals[0]['time_start']);

                $last = array_pop($signals);

//                debug($pair['pair'] . ' | ' . $capital / $first_price . ' | ' . $diff . ' | ' . ( ($capital - $first_price) * 365) / ($first_price * $diff) * 100);

                debug($pair['pair'] . ' | ' .
                    $capital / $first_price . ' | ' .
                    (($capital - $first_price) * 365) / ($first_price * $diff) * 100 . ' | ' .
                    $last['time_start'] . ' | ' .
                    $diff . ' | ' .
                    $first_price . ' | ' .
                    $last['close'] . ' | ' .
                    $last['close'] / $first_price
                );

            } else {

                debug($pair['pair'] . ' Not enough candles');

            }

        }

    }

    public function testTinkoff()
    {

        $tickers = TinkoffTicker::all()->toArray();

//        $tickers = TinkoffTicker::where('ticker', 'ZYNE')->get()->toArray();

        foreach ($tickers as $ticker) {

            $candles = TinkoffDayCandle::where('tinkoff_ticker_id', $ticker['id'])
                ->orderBy('time_start', 'desc')->take(20000)->get()->toArray();

            $candles = array_reverse($candles);

            $hist = Strategy::macd($candles, 200, 400, 9, true);

            $signals = $this->getSignalMacd($hist);

//            array_pop($signals);

            if (!empty($signals)) {

                $capital = $this->priceLongFlexibleCapital($signals, $first_price);

                $realTime = Carbon::now();

                $diff = $realTime->diffInDays($signals[0]['time_start']);

                $last = array_pop($signals);

                debug($ticker['ticker'] . ' | ' .
                    $capital / $first_price . ' | ' .
                    (($capital - $first_price) * 365) / ($first_price * $diff) * 100 . ' | ' .
                    $last['time_start'] . ' | ' .
                    $diff . ' | ' .
                    $first_price . ' | ' .
                    $last['close'] . ' | ' .
                    $last['close'] / $first_price
                );

            } else {

                debug($ticker['ticker'] . ' Not enough candles');

            }

//            debug($signals);

        }

    }

    public function testEmaBinance()
    {

        $pairs = BinancePair::all()->toArray();

//        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();

        foreach ($pairs as $pair) {

            $candles = BinanceDayCandle::where('binance_pair_id', $pair['id'])
                ->orderBy('time_start', 'desc')->take(5000)->get()->toArray();

            $candles = array_reverse($candles);

            $ema = Strategy::ema($candles, 100);

            $signals = $this->getSignalEma($candles, $ema);

            if (count($signals) > 1) {

                $capital = $this->priceLongCapital($signals, $first_price);

                $realTime = Carbon::now();

                $diff = $realTime->diffInDays($signals[0]['time_start']);

                $last = array_pop($signals);

//                debug($pair['pair'] . ' | ' . $capital / $first_price . ' | ' . $diff . ' | ' . ( ($capital - $first_price) * 365) / ($first_price * $diff) * 100);

                debug($pair['pair'] . ' | ' .
                    $capital / $first_price . ' | ' .
                    (($capital - $first_price) * 365) / ($first_price * $diff) * 100 . ' | ' .
                    $last['time_start'] . ' | ' .
                    $diff . ' | ' .
                    $first_price . ' | ' .
                    $last['close'] . ' | ' .
                    $last['close'] / $first_price
                );

            } else {

                debug($pair['pair'] . ' Not enough candles');

            }

        }

    }

    private function getSignalEma($candles, $ema)
    {

        foreach ($candles as $key => $candle) {

            if ($ema[$key] != 0) {

                if ($ema[$key] <= $candle['close']) {

                    $signal_pre['signal'] = 'long';

                } else {

                    $signal_pre['signal'] = 'short';

                }

                $signal_pre['close'] = $candle['close'];

                $signal_pre['time_start'] = $candle['time_start'];

                $signals[] = $signal_pre;

            }

        }

        if (isset($signals)) {

            $signals = array_values($signals);

            foreach ($signals as $key => $signal) {

                if (isset($prev)) {

                    if ($prev['signal'] == $signal['signal']) {

                        unset($signals[$key]);

                    } else {

                        $prev = $signal;

                    }

                } else {

                    $prev = $signal;

                }

            }

            $signals = array_values($signals);

        }


        return $signals ?? [];

    }

    private function getSignalMacd($hist)
    {

        foreach ($hist as $h) {

            if (isset($prev)) {

                if ($h['hist'] <= 0 && $prev['hist'] >= 0) {

                    $signal_pre['signal'] = 'short';
                    $signal_pre['close'] = $h['close'];
                    $signal_pre['time_start'] = $h['time_start'];

                    $signals[] = $signal_pre;

                } elseif ($h['hist'] >= 0 && $prev['hist'] <= 0) {

                    $signal_pre['signal'] = 'long';
                    $signal_pre['close'] = $h['close'];
                    $signal_pre['time_start'] = $h['time_start'];

                    $signals[] = $signal_pre;

                }

            }

            $prev = $h;

        }

        return $signals ?? [];

    }

    private function priceCapital($signals, &$first_price)
    {

        foreach ($signals as $signal) {

            if (isset($capital)) {

                if ($signal['signal'] == 'long') $capital -= 2 * $signal['close'];
                elseif ($signal['signal'] == 'short') $capital += 2 * $signal['close'];
                else throw new \Exception();

            } else {

                if ($signal['signal'] == 'long') $capital = -$signal['close'];
                elseif ($signal['signal'] == 'short') $capital = $signal['close'];
                else throw new \Exception();

                $first_price = $capital;

            }

        }

        if (isset($capital) && isset($signal)) {

            if ($signal['signal'] == 'long') $capital += $signal['close'];
            elseif ($signal['signal'] == 'short') $capital -= $signal['close'];
            else throw new \Exception();

        }

        return $capital ?? null;

    }

    private function priceLongCapital($signals, &$first_price)
    {

        foreach ($signals as $signal) {

            if (isset($capital)) {

                if ($signal['signal'] == 'long') $capital -= $signal['close'];
                elseif ($signal['signal'] == 'short') $capital += $signal['close'];
                else throw new \Exception();

            } else {

                if ($signal['signal'] == 'long') $capital = -$signal['close'];

                $first_price = $signal['close'];

            }

        }

        if (isset($capital) && isset($signal) && $signal['signal'] == 'long') $capital += $signal['close'];

        return $capital ?? null;

    }

    private function priceLongFlexibleCapital($signals, &$first_price)
    {

        foreach ($signals as $signal) {

            if (isset($first)) {

                if (isset($capital)) {

                    if ($signal['signal'] == 'long') $prev = $signal['close'];
                    elseif ($signal['signal'] == 'short') $capital = $capital / $prev * $signal['close'];
                    else throw new \Exception();

                } else {

                    $capital = $signal['close'];

                }

            } else {

                if ($signal['signal'] == 'long') {

                    $first = true;

                    $first_price = $signal['close'];

                }

            }

        }

        return $capital ?? null;

    }

}
