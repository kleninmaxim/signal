<?php

namespace App\Src;

use App\Models\BinancePair;
use App\Models\BinanceFiveMinuteCandle;
use App\Models\BinanceFifteenMinuteCandle;
use App\Models\BinanceThirtyMinuteCandle;
use App\Models\BinanceHourCandle;
use App\Models\BinanceFourHourCandle;
use App\Models\BinanceDayCandle;
use App\Src\Strategy;
use Carbon\Carbon;
use App\Models\TinkoffDayCandle;
use App\Models\TinkoffTicker;
use App\Models\TinkoffFourHourCandle;
use App\Models\TinkoffHourCandle;

class StrategyTest
{

    public function testStrategyBinance()
    {


        $pairs = BinancePair::all()->toArray();

        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();

        foreach ($pairs as $pair) {

            $candles = BinanceThirtyMinuteCandle::where('binance_pair_id', $pair['id'])
                ->orderBy('time_start', 'desc')->take(1000)->get()->toArray();

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

            foreach ($strategies as $strategy) {

                if (isset($prev_dot)) {

                    if ($prev_dot != $strategy['dots']) {

                        if ($profit == 0 && $stop == 0) {

                            if ($strategy['dots'] == $strategy['ema'] && $strategy['dots'] == $strategy['hist']) {

                                $profit = $strategy['profit'];

                                $stop = $strategy['stop'];

                                $close = $strategy['close'];

                                $time_start = $strategy['time_start'];

                                $current_dot = $strategy['dots'];

                            }

                        }

                    }

                    if ($profit != 0 && $stop != 0) {

                        $capital_pre['time_start'] = $time_start;

                        $capital_pre['close'] = $close;

                        if ($current_dot == 'long') {

                            if ($strategy['high'] >= $profit) {

                                $capital_pre['profit'] = $profit - $close;

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

                            if ($strategy['low'] <= $stop) {

                                $capital_pre['profit'] = $stop - $close;

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

                        }
                        /* else {

                            if ($strategy['high'] >= $stop) {

                                $capital_pre['profit'] = $close - $stop;

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

                            if ($strategy['low'] <= $profit) {

                                $capital_pre['profit'] = $close - $profit;

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

                        }*/

                    }

                }

                $prev_dot = $strategy['dots'];

            }

            $sum = 0;

            foreach ($capital as $c) {

                $sum += $c['profit'];

            }

            $conseq = $conseq ? array_count_values($conseq) : [];

            ksort($conseq);

            foreach ($conseq as $c) {

                $conseq_percentage[] = round($c / array_sum($conseq) * 100, 2);

            }

            debug($sum ?? []);
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
                        $p_c ++;
                    } else {
                        $minus += $arr;
                        $m_c ++;
                    }

                }

                debug($pair['pair'] . ' | ' . $plus / $p_c * 100 . ' | ' . $minus / $m_c * 100);

                $output = array_slice(array_reverse($array), 0,round(count($array) * 0.1618034));

                $plus = 0;
                $minus = 0;
                $p_c = 0;
                $m_c = 0;
                $all = [];

                foreach ($output as $arr) {

                    if ($arr > 0) {
                        $plus += $arr;
                        $p_c ++;
                    } else {
                        $minus += $arr;
                        $m_c ++;
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
                    ( ($capital - $first_price) * 365) / ($first_price * $diff) * 100 . ' | '  .
                    $last['time_start'] . ' | '  .
                    $diff . ' | '  .
                    $first_price . ' | '  .
                    $last['close'] . ' | '  .
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
                    ( ($capital - $first_price) * 365) / ($first_price * $diff) * 100 . ' | '  .
                    $last['time_start'] . ' | '  .
                    $diff . ' | '  .
                    $first_price . ' | '  .
                    $last['close'] . ' | '  .
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
                    ( ($capital - $first_price) * 365) / ($first_price * $diff) * 100 . ' | '  .
                    $last['time_start'] . ' | '  .
                    $diff . ' | '  .
                    $first_price . ' | '  .
                    $last['close'] . ' | '  .
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
                elseif($signal['signal'] == 'short') $capital += 2 * $signal['close'];
                else throw new \Exception();

            } else {

                if ($signal['signal'] == 'long') $capital = -$signal['close'];
                elseif($signal['signal'] == 'short') $capital = $signal['close'];
                else throw new \Exception();

                $first_price = $capital;

            }

        }

        if (isset($capital) && isset($signal)) {

            if ($signal['signal'] == 'long') $capital += $signal['close'];
            elseif($signal['signal'] == 'short') $capital -= $signal['close'];
            else throw new \Exception();

        }

        return $capital ?? null;

    }

    private function priceLongCapital($signals, &$first_price)
    {

        foreach ($signals as $signal) {

            if (isset($capital)) {

                if ($signal['signal'] == 'long') $capital -= $signal['close'];
                elseif($signal['signal'] == 'short') $capital += $signal['close'];
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
                    elseif($signal['signal'] == 'short') $capital = $capital / $prev * $signal['close'];
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