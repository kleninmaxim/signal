<?php

namespace App\Http\Controllers;

use App\Hiney\BinanceFutures;
use App\Hiney\Indicators\AtrBands;
use App\Hiney\Indicators\CoraWave;
use App\Hiney\Indicators\Ema;
use App\Hiney\Indicators\HeikinAshi;
use App\Hiney\Indicators\Mfi;
use App\Hiney\Strategies\TheHineyMoneyFlow;
use App\Jobs\BinanceTestJob;
use App\Src\Binance;
use App\Src\Capital;
use App\Src\Math;
use App\Src\Strategy;

use App\Models\BinancePair;

class BinanceController extends Controller
{

    private $binance;

    public function __construct()
    {
        $this->binance = new Binance();
    }

    public function count($base, $price, $stop)
    {

        $base = strtoupper($base);

        $balances = (new BinanceFutures())->getBalances();

        $position_take = Math::round($balances['availableBalance'] * 0.05);

        $usdt = $position_take * $price / abs($price - $stop);

        $take = $price + ($price - $stop);

        debug('usdt');
        debug($usdt);

        debug('price');
        debug($price);

        debug('take');
        debug($take);

        debug('stop');
        debug($stop);

    }

    public function testHineyStrategy()
    {

        $candles = array_values($this->binance->getCandles('BTC/USDT', '1d'));

        debug($candles, true);

//        $candles = array_values(
//            array_filter($candles, function($candle) {
//                return $candle['time_start'] >= '2021-01-01 06:00:00';
//            })
//        );

//        $time_start = (time() - 7 * 24 * 60 * 60 * 12 * 5) * 1000;
//
//        $pair_str = str_replace('/', '', 'BTC/USDT');
//
//        $candles = $this->binance->getCandlesApiFormat($pair_str, '5m', $time_start);

        if (count($candles) >= 50) {

            (new HeikinAshi())->put($candles);

            (new Mfi())->put($candles);

            //(new Ema(200))->put($candles);

            (new AtrBands(atr_multiplier_upper:  5, atr_multiplier_lower: 5))->put($candles);

        } else {

            debug('Candles less than 50');

        }

        $candles = array_values(
            array_filter($candles, function ($candle) {
                return !empty($candle['mfi']) && !empty($candle['atr_band_upper']);
            })
        );

        $position = [];

        $profits = [];

        $PL = 1;

        foreach ($candles as $key => $candle) {

            if (empty($position)) {

                $current_position_heikin_ashi = ($candle['haOpen'] > $candle['haClose']) ? 'sell' : 'buy';

                //$current_position_ema = $candle['close'] < $candle['ema'] ? 'sell' : 'buy';

                $current_short_position =
                    $candle['mfi'] >= 79 && $current_position_heikin_ashi == 'sell'/* && $current_position_ema == 'sell'*/;

                $current_long_position =
                    $candle['mfi'] <= 21 && $current_position_heikin_ashi == 'buy'/* && $current_position_ema == 'buy'*/;

                for ($i = 1; $i <= 10 ; $i++) {

                    if (isset($candles[$key - $i])) {

                        $candle_position_heikin_ashi = ($candles[$key - $i]['haOpen'] > $candles[$key - $i]['haClose']) ? 'sell' : 'buy';

                        if ($candle_position_heikin_ashi != $current_position_heikin_ashi) {

                            if (
                                (
                                    $candles[$key - $i]['mfi'] >= 79 &&
                                    $candle_position_heikin_ashi == 'buy' /*&&
                                    $current_position_ema == 'buy'*/
                                ) || $current_short_position
                            ) {

                                $position = [
                                    'position' => $current_position_heikin_ashi,
                                    'price' => $candle['close'],
                                    'stop_loss' => $candle['atr_band_upper'],
                                    'take_profit' => ($PL + 1) * $candle['close'] - $PL * $candle['atr_band_upper'],
                                    'time_start' => $candle['time_start']
                                ];

                            } elseif (
                                (
                                    $candles[$key - $i]['mfi'] <= 21 &&
                                    $candle_position_heikin_ashi == 'sell' /*&&
                                    $current_position_ema == 'sell'*/
                                ) || $current_long_position
                            ) {

                                $position = [
                                    'position' => $current_position_heikin_ashi,
                                    'price' => $candle['close'],
                                    'stop_loss' => $candle['atr_band_lower'],
                                    'take_profit' => ($PL + 1) * $candle['close'] - $PL * $candle['atr_band_lower'],
                                    'time_start' => $candle['time_start']
                                ];

                            }

                        } else
                            break;

                    } else
                        break;

                }

            } else {

                if ($position['position'] == 'sell') {

                    if ($candle['high'] >= $position['stop_loss']) {

                        $profits[] = [
                            'time_start' => $position['time_start'],
                            'profit' => -4
                        ];

                        $position = [];

                    } elseif($candle['low'] <= $position['take_profit']) {

                        $profits[] = [
                            'time_start' => $position['time_start'],
                            'profit' => 4 * $PL
                        ];

                        $position = [];

                    }

                } else {

                    if ($candle['low'] <= $position['stop_loss']) {

                        $profits[] = [
                            'time_start' => $position['time_start'],
                            'profit' => -4
                        ];

                        $position = [];

                    } elseif($candle['high'] >= $position['take_profit']) {

                        $profits[] = [
                            'time_start' => $position['time_start'],
                            'profit' => 4 * $PL
                        ];

                        $position = [];

                    }

                }

            }

        }

        $profit = array_column($profits, 'profit');

//        $j = 2;
//
//        foreach ($profit as $key => $pr) {
//
//            if (isset($profit[$key - 1])) {
//
//                if ($profit[$key - 1] <= 0) {
//                    $profit[$key] = $pr * $j;
//                    $j *= 2;
//                } else
//                    $j = 2;
//
//            }
//
//        }
//
//        debug($profit, true);

        $n = 0;
        $p = 0;

        $i = 0;
        $j = 0;

        $sequence_of_negative = [];

        $sequence_of_positive = [];

        $negative = [];

        foreach ($profit as $pr) {

            if ($pr <= 0) {

                $i++;

                $n++;

                if ($j != 0) $sequence_of_positive[] = $j;

                $j = 0;

                $negative[] = $pr;

            } else {

                if ($i != 0) $sequence_of_negative[] = $i;

                $j++;

                $p++;

                $i = 0;
            }

        }

        if ($j != 0) $sequence_of_positive[] = $j;

        if ($i != 0) $sequence_of_negative[] = $i;

        $sequence_of_negative = array_count_values($sequence_of_negative);
        $sequence_of_positive = array_count_values($sequence_of_positive);

        ksort($sequence_of_negative);
        ksort($sequence_of_positive);
        sort($negative);

        debug($sequence_of_negative);
        debug($sequence_of_positive);
        debug(Math::change($p, $p + $n, 4) * 100);
        debug($p);
        debug($n);
        debug('Profit: ' . array_sum($profit));
        debug('Max loss: ' . min($profit));

        //debug($profit);

    }

    public function random()
    {

        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();

        $timeframe = '5m';

        $percentage = 5;

        debug($timeframe);

        foreach ($pairs as $pair) {

            debug($pair['pair']);

            $candles = array_values($this->binance->getCandles($pair['pair'], $timeframe));

            $position = [];

            $profits = [];

            foreach ($candles as $candle) {

                if (empty($position)) {

                    $sell_or_buy = (rand(0,1) == 0) ? 'buy' : 'sell';

                    if ($sell_or_buy == 'sell') {

                        $position = [
                            'position' => $sell_or_buy,
                            'time_start' => $candle['time_start'],
                            'price' => $candle['close'],
                            'take_profit' => $candle['close'] * (1 - $percentage / 100),
                            'stop_loss' => $candle['close'] * (1 + $percentage / 100),
                        ];

                    } else {

                        $position = [
                            'position' => $sell_or_buy,
                            'time_start' => $candle['time_start'],
                            'price' => $candle['close'],
                            'take_profit' => $candle['close'] * (1 + $percentage / 100),
                            'stop_loss' => $candle['close'] * (1 - $percentage / 100),
                        ];

                    }

                } else {

                    if ($position['position'] == 'sell') {

                        if ($position['stop_loss'] <= $candle['high']) {

                            $profits[] = [
                                'time_start' => $position['time_start'],
                                'time_end' => $candle['time_start'],
                                'profit_percentage' => -$percentage,
                            ];

                            $position = [];

                        } elseif ($position['take_profit'] > $candle['low']) {

                            $profits[] = [
                                'time_start' => $position['time_start'],
                                'time_end' => $candle['time_start'],
                                'profit_percentage' => $percentage,
                            ];

                            $position = [];

                        }

                    } else {

                        if ($position['stop_loss'] >= $candle['low']) {

                            $profits[] = [
                                'time_start' => $position['time_start'],
                                'time_end' => $candle['time_start'],
                                'profit_percentage' => -$percentage,
                            ];

                            $position = [];

                        } elseif ($position['take_profit'] < $candle['high']) {

                            $profits[] = [
                                'time_start' => $position['time_start'],
                                'time_end' => $candle['time_start'],
                                'profit_percentage' => $percentage,
                            ];

                            $position = [];


                        }

                    }

                }

            }

            $profit = array_column($profits, 'profit_percentage');

            $n = 0;
            $p = 0;

            $i = 0;
            $j = 0;

            $sequence_of_negative = [];

            $sequence_of_positive = [];

            $negative = [];

            $sum = 1;

            foreach ($profit as $pr) {

                if ($pr <= 0) {

                    $i++;

                    $n++;

                    if ($j != 0) $sequence_of_positive[] = $j;

                    $j = 0;

                    $negative[] = $pr;

                } else {

                    if ($i != 0) $sequence_of_negative[] = $i;

                    $j++;

                    $p++;

                    $i = 0;
                }

                $sum *= (1 + $pr / 100);

            }

            if ($j != 0) $sequence_of_positive[] = $j;

            if ($i != 0) $sequence_of_negative[] = $i;

            $sequence_of_negative = array_count_values($sequence_of_negative);
            $sequence_of_positive = array_count_values($sequence_of_positive);

            ksort($sequence_of_negative);
            ksort($sequence_of_positive);
            sort($negative);
//            sort($profit);
//
//            debug($profit);

            debug($sequence_of_negative);
            debug($sequence_of_positive);
            debug($p);
            debug($n);

            debug(Math::change($p, $p + $n, 4) * 100);
            debug(Math::round(array_sum($profit)));
            debug(Math::round(($sum - 1) * 100));

        }

    }

    public function randomOnlyLong()
    {

        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();

        $timeframe = '5m';

        $percentage = 2;

        debug($timeframe);

        foreach ($pairs as $pair) {

            debug($pair['pair']);

            $candles = array_values($this->binance->getCandles($pair['pair'], $timeframe));

            $position = [];

            $profits = [];

            foreach ($candles as $candle) {

                if (empty($position)) {

                    $sell_or_buy = 'buy';

                    if ($sell_or_buy == 'sell') {

                        $position = [
                            'position' => $sell_or_buy,
                            'time_start' => $candle['time_start'],
                            'price' => $candle['close'],
                            'take_profit' => $candle['close'] * (1 - $percentage / 100),
                            'stop_loss' => $candle['close'] * (1 + $percentage / 100),
                        ];

                    } else {

                        $position = [
                            'position' => $sell_or_buy,
                            'time_start' => $candle['time_start'],
                            'price' => $candle['close'],
                            'take_profit' => $candle['close'] * (1 + $percentage / 100),
                            'stop_loss' => $candle['close'] * (1 - $percentage / 100),
                        ];

                    }

                } else {

                    if ($position['position'] == 'sell') {

                        if ($position['stop_loss'] <= $candle['high']) {

                            $profits[] = [
                                'time_start' => $position['time_start'],
                                'time_end' => $candle['time_start'],
                                'profit_percentage' => -$percentage,
                            ];

                            $position = [];

                        } elseif ($position['take_profit'] > $candle['low']) {

                            $profits[] = [
                                'time_start' => $position['time_start'],
                                'time_end' => $candle['time_start'],
                                'profit_percentage' => $percentage,
                            ];

                            $position = [];

                        }

                    } else {

                        if ($position['stop_loss'] >= $candle['low']) {

                            $profits[] = [
                                'time_start' => $position['time_start'],
                                'time_end' => $candle['time_start'],
                                'profit_percentage' => -$percentage,
                            ];

                            $position = [];

                        } elseif ($position['take_profit'] < $candle['high']) {

                            $profits[] = [
                                'time_start' => $position['time_start'],
                                'time_end' => $candle['time_start'],
                                'profit_percentage' => $percentage,
                            ];

                            $position = [];


                        }

                    }

                }

            }

            $profit = array_column($profits, 'profit_percentage');

            $n = 0;
            $p = 0;

            $i = 0;
            $j = 0;

            $sequence_of_negative = [];

            $sequence_of_positive = [];

            $negative = [];

            $sum = 1;

            foreach ($profit as $pr) {

                if ($pr <= 0) {

                    $i++;

                    $n++;

                    if ($j != 0) $sequence_of_positive[] = $j;

                    $j = 0;

                    $negative[] = $pr;

                } else {

                    if ($i != 0) $sequence_of_negative[] = $i;

                    $j++;

                    $p++;

                    $i = 0;
                }

                $sum *= (1 + $pr / 100);

            }

            if ($j != 0) $sequence_of_positive[] = $j;

            if ($i != 0) $sequence_of_negative[] = $i;

            $sequence_of_negative = array_count_values($sequence_of_negative);
            $sequence_of_positive = array_count_values($sequence_of_positive);

            ksort($sequence_of_negative);
            ksort($sequence_of_positive);
            sort($negative);
//            sort($profit);
//
//            debug($profit);

            debug($sequence_of_negative);
            debug($sequence_of_positive);
            debug($p);
            debug($n);

            debug(Math::change($p, $p + $n, 4) * 100);
            debug(Math::round(array_sum($profit)));
            debug(Math::round(($sum - 1) * 100));

        }

    }

    public function coraWave()
    {

        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();
        //$pairs = BinancePair::where('id', '<=', 48)->get()->toArray();

        $timeframe = '5m';

        debug($timeframe);

        foreach ($pairs as $pair) {

            debug($pair['pair']);

            $candles = array_values($this->binance->getCandles($pair['pair'], $timeframe));

//        $candles = array_values(
//            array_filter($candles, function($candle) {
//                return $candle['time_start'] >= '2021-01-01 06:00:00';
//            })
//        );

//            $time_start = (time() - 7 * 24 * 60 * 60 * 12 * 5) * 1000;
//
//            $candles = $this->binance->getCandlesApiFormat(str_replace('/', '', $pair['pair']), '5m', $time_start);

            if (count($candles) >= 20) {

                (new CoraWave(12))->put($candles);

                (new AtrBands(atr_multiplier_upper:  2, atr_multiplier_lower: 2))->put($candles);

            } else {

                debug('Candles less than 20 for ' . $pair['pair']);

                continue;

            }

            $candles = array_slice(
                array_values(
                    array_filter($candles, function ($candle) {
                        return $candle['cora_wave'] > 0;
                    })
                ),
                2
            );

            $position = [];

            $profits = [];

            foreach ($candles as $key => $candle) {

                if (isset($candles[$key - 1])) {

                    $position_cora_wave = ($candle['cora_wave'] >= $candles[$key - 1]['cora_wave']) ? 'buy' : 'sell';

                    if (empty($position)) {

                        $position = [
                            'position' => $position_cora_wave,
                            'price' => $candle['close'],
                            'time_start' => $candle['time_start']
                        ];

                    } else {

                        if ($position['position'] != $position_cora_wave) {

                            $change = Math::percentage($position['price'], $candle['close']);

                            if ($position['position'] == 'sell') {

                                $profits[] = [
                                    'time_start' => $position['time_start'],
                                    'time_end' => $candle['time_start'],
                                    'profit_percentage' => ($change >= 50) ? -50 : -1 * $change - 0.1
                                ];

                            } else {

                                $profits[] = [
                                    'time_start' => $position['time_start'],
                                    'time_end' => $candle['time_start'],
                                    'profit_percentage' => $change - 0.1
                                ];

                            }

                            $position = [
                                'position' => $position_cora_wave,
                                'price' => $candle['close'],
                                'time_start' => $candle['time_start']
                            ];

                        }

                    }

                }

            }

            $profit = array_column($profits, 'profit_percentage');


            $n = 0;
            $p = 0;

            $i = 0;
            $j = 0;

            $sequence_of_negative = [];

            $sequence_of_positive = [];

            $negative = [];

            $sum = 1;

            foreach ($profit as $pr) {

                if ($pr <= 0) {

                    $i++;

                    $n++;

                    if ($j != 0) $sequence_of_positive[] = $j;

                    $j = 0;

                    $negative[] = $pr;

                } else {

                    if ($i != 0) $sequence_of_negative[] = $i;

                    $j++;

                    $p++;

                    $i = 0;
                }

                $sum *= (1 + $pr / 100);

            }

            if ($j != 0) $sequence_of_positive[] = $j;

            if ($i != 0) $sequence_of_negative[] = $i;

            $sequence_of_negative = array_count_values($sequence_of_negative);
            $sequence_of_positive = array_count_values($sequence_of_positive);

            ksort($sequence_of_negative);
            ksort($sequence_of_positive);
            sort($negative);
//            sort($profit);
//
//            debug($profit);

            debug($sequence_of_negative);
            debug($sequence_of_positive);
            debug($p);
            debug($n);

            debug(Math::change($p, $p + $n, 4) * 100);
            debug(Math::round(array_sum($profit)));
            debug(Math::round(($sum - 1) * 100));

            $sums[] = Math::round(($sum - 1) * 100);
            $sums_sum[] = Math::round(array_sum($profit));

        }

        debug('SUMS: ');
        debug(count($sums));
        debug(array_sum($sums));
        debug(array_sum($sums) / count($sums));
        debug('SUMS SUMS: ');
        debug(count($sums));
        debug(array_sum($sums_sum));
        debug(array_sum($sums_sum) / count($sums));

    }

    public function processTokens()
    {

        $pairs = BinancePair::where('id', '<=', 300)->get()->toArray();

        $time_start = (time() - 30 * 24 * 60 * 60 * 12 * 5) * 1000;

        foreach ($pairs as $pair)
        {

            $pair_str = str_replace('/', '', $pair['pair']);

            $candles = $this->binance->getCandlesApiFormat($pair_str, '1M', $time_start);

            $first = array_shift($candles)['close'];
            $last = array_pop($candles)['close'];

            debug($pair['pair'] . ' | ' . Math::percentage($first, $last));


        }

    }

    public function testFivePercentageChangeStrategy()
    {

        $pairs = BinancePair::where('id', '<=', 300)->get()->toArray();
        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();

        $time_start = (time() - 30 * 24 * 60 * 60 * 2) * 1000;

        $pers = [1, 2, 3, 4];

        foreach ($pairs as $pair) {

            //$pair_str = str_replace('/', '', $pair['pair']);

            $candles = $this->binance->getCandles($pair['pair'], '1d');
            //$candles = $this->binance->getCandlesApiFormat($pair_str, '1d', $time_start);

/*            $candles = array_filter($candles, function ($candle) {
                return $candle['time_start'] <= '2019-03-18 00:00:00' && $candle['time_start'] >= '2017-12-18 00:00:00';
            });*/

            foreach ($pers as $per) {

                $data = Strategy::FivePercentage(
                    $candles,
                    $per
                );

                if (!empty($data)) {

/*                    $n = 0;
                    $p = 0;

                    $i = 0;
                    $j = 0;

                    $sequence_of_negative = [];

                    $sequence_of_positive = [];

                    $negative = [];*/

                    $sum = 1;

                    $sums = [];

                    foreach ($data as $datum) {

/*                        if ($datum <= 0) {

                            $i++;

                            $n++;

                            if ($j != 0) $sequence_of_positive[] = $j;

                            $j = 0;

                            $negative[] = $datum;

                        } else {

                            if ($i != 0) $sequence_of_negative[] = $i;

                            $j++;

                            $p++;

                            $i = 0;
                        }*/

                        $sum *= (1 + $datum / 100);

                        $sums[] = (string) Math::round($sum);

                        echo $amount=str_replace(".",",", Math::round($sum)) . "</br>";

                    }

/*                    if ($j != 0) $sequence_of_positive[] = $j;

                    if ($i != 0) $sequence_of_negative[] = $i;

                    $sequence_of_negative = array_count_values($sequence_of_negative);
                    $sequence_of_positive = array_count_values($sequence_of_positive);

                    ksort($sequence_of_negative);
                    ksort($sequence_of_positive);
                    sort($negative);
                    sort($data);*/

                    debug(
                        $pair['pair'] . ' | ' . $per . ' | ' . Math::round(($sum - 1) * 100) . ' | ' . array_sum($data)
                    );

                    debug($sums ?? []);

                    $high = 0;
                    $min = 0;

                    $mins = [];

                    foreach ($sums as $sum) {

                        if ($sum >= $high) {

                            $high = $sum;

                            $min = $sum;

                        } elseif ($sum <= $min) {

                            $min = $sum;

                            $mins[] = Math::percentage($high, $min);

                        }

                    }

                    asort($mins);

                    debug($mins);

                    //debug($data);

                    //debug($sequence_of_negative);
                    //debug($sequence_of_positive);
                    //debug(Math::change($p, $p + $n, 4) * 100);
                    //debug($p);
                    //debug($n);

                    //debug(Math::statisticAnalyse($data));

                    //debug($negative);

                }

                //debug($data);
                //debug($date);

            }

        }

    }

    public function testFivePercentageChangeStrategyWithSell()
    {

        $pairs = BinancePair::where('id', '<=', 300)->get()->toArray();
        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();

        $time_start = (time() - 30 * 24 * 60 * 60 * 2) * 1000;

        $pers = [1, 2, 3, 4];

        foreach ($pairs as $pair) {

            //$pair_str = str_replace('/', '', $pair['pair']);

            $candles = $this->binance->getCandles($pair['pair'], '1d');
            //$candles = $this->binance->getCandlesApiFormat($pair_str, '1d', $time_start);

            /*            $candles = array_filter($candles, function ($candle) {
                            return $candle['time_start'] <= '2019-03-18 00:00:00' && $candle['time_start'] >= '2017-12-18 00:00:00';
                        });*/

            foreach ($pers as $per) {

                $data = Strategy::FivePercentageWithSell(
                    $candles,
                    $per
                );

                if (!empty($data)) {

                    /*                    $n = 0;
                                        $p = 0;

                                        $i = 0;
                                        $j = 0;

                                        $sequence_of_negative = [];

                                        $sequence_of_positive = [];

                                        $negative = [];*/

                    $sum = 1;

                    $sums = [];

                    foreach ($data as $datum) {

                        /*                        if ($datum <= 0) {

                                                    $i++;

                                                    $n++;

                                                    if ($j != 0) $sequence_of_positive[] = $j;

                                                    $j = 0;

                                                    $negative[] = $datum;

                                                } else {

                                                    if ($i != 0) $sequence_of_negative[] = $i;

                                                    $j++;

                                                    $p++;

                                                    $i = 0;
                                                }*/

                        $sum *= (1 + $datum / 100);


                        $sums[] = Math::round($sum);

                    }

                    /*                    if ($j != 0) $sequence_of_positive[] = $j;

                                        if ($i != 0) $sequence_of_negative[] = $i;

                                        $sequence_of_negative = array_count_values($sequence_of_negative);
                                        $sequence_of_positive = array_count_values($sequence_of_positive);

                                        ksort($sequence_of_negative);
                                        ksort($sequence_of_positive);
                                        sort($negative);
                                        sort($data);*/

                    debug(
                        $pair['pair'] . ' | ' . $per . ' | ' . Math::round(($sum - 1) * 100) . ' | ' . array_sum($data)
                    );

                    debug($sums);

                    //debug($data);

                    //debug($sequence_of_negative);
                    //debug($sequence_of_positive);
                    //debug(Math::change($p, $p + $n, 4) * 100);
                    //debug($p);
                    //debug($n);

                    //debug(Math::statisticAnalyse($data));

                    //debug($negative);

                }

                //debug($data);
                //debug($date);

            }

        }

    }

    public function testFinalStrategy()
    {
        // добавь медиану, квантиль, мода
        // сделать дерево решений, добавить стратегию, трейдинг активами.

        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();
        $pairs = BinancePair::where('id', '<=', 48)->get()->toArray();

        $timeframe = '1h';
        $profit = 0.5;

//        $time_start = (time() - 7 * 24 * 60 * 60 * 12 * 5) * 1000;
//
//        $pair_str = str_replace('/', '', 'BTC/USDT');
//
//        $candles = $this->binance->getCandlesApiFormat($pair_str, '5m', $time_start);

        debug($timeframe);
        debug($profit);

        foreach ($pairs as $pair) {

            list($data, $highs) = Strategy::finalSimple(
                $this->binance->getCandles($pair['pair'], $timeframe),
                12,
                $profit
            );

            if (!empty($data)) {

                $n = 0;
                $p = 0;

                $i = 0;
                $j = 0;

                $sequence_of_negative = [];

                $sequence_of_positive = [];

                $negative = [];

                $sum = 1;

                foreach ($data as $datum) {

                    if ($datum <= 0) {

                        $i++;

                        $n++;

                        if ($j != 0) $sequence_of_positive[] = $j;

                        $j = 0;

                        $negative[] = $datum;

                    } else {

                        if ($i != 0) $sequence_of_negative[] = $i;

                        $j++;

                        $p++;

                        $i = 0;
                    }

                    $sum *= (1 + $datum / 100);

                }

                if ($j != 0) $sequence_of_positive[] = $j;

                if ($i != 0) $sequence_of_negative[] = $i;

                $sequence_of_negative = array_count_values($sequence_of_negative);
                $sequence_of_positive = array_count_values($sequence_of_positive);

                ksort($sequence_of_negative);
                ksort($sequence_of_positive);
                sort($negative);
                sort($data);

                debug($pair['pair']);

//                debug($highs);
//                debug($data);
//                debug($sequence_of_negative);
//                debug($sequence_of_positive);
//                debug($p);
//                debug($n);

                debug(Math::change($p, $p + $n, 4) * 100);
                debug(Math::round(array_sum($data)));
                debug(Math::round(($sum - 1) * 100));

                //debug(Math::statisticAnalyse($data));

                //debug($negative);

            }

        }

    }

    public function test()
    {

        $pairs = BinancePair::where('pair', 'ZRX/USDT')->get();
        $pairs = BinancePair::all();

        $output = [];

        $sum = 0;
        $sum_apy = 0;
        $real_apy_sum = 0;
        $day = 1;

        $count = count($pairs);

        foreach ($pairs as $pair) {

            $result = Capital::simple(
                Strategy::coraWaveSimple(
                    $this->binance->getCandles($pair->pair, '1w'),
                    12
                )
            );

            if ($result['indicators'] != null) {

                $sampling = array_column($result['indicators'], 'profit_percentage');

                $output = array_merge($output, $sampling);

                $sum += $result['final']['profit_percentage_sum'];
                $sum_apy += $result['final']['profit_percentage_apy_sum'];
                $day = max($day, $result['final']['days']);

                if ($result['final']['days'] >= 365) {

                    $real_apy = (pow(($result['final']['profit_percentage_sum'] / 100 + 1), 365 / $result['final']['days']) - 1) * 100;

                } else {

                    $real_apy = 0;

                }

                $real_apy_sum += $real_apy;

            }

        }

        debug(Math::statisticAnalyse($output));
        debug(
            'I: ' . $sum / $count . "\n" .
            'Days: ' . $day . "\n" .
            'APY: ' . $sum / $count * 365 / $day . "\n" .
            'Sum APY: ' . $sum_apy / $count . "\n" .
            'Real APY: ' . $real_apy_sum / $count . "\n\n"
        );

        /*        dispatch(new BinanceTestJob(
                        '1d',
                        12,
                        'simple',
                        'complex'
                    )
                );

                dispatch(new BinanceTestJob(
                        '4h',
                        12,
                        'quick',
                        'simple'
                    )
                );

                dispatch(new BinanceTestJob(
                        '4h',
                        12,
                        'quick',
                        'complex'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1h',
                        12,
                        'quick',
                        'simple'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1h',
                        12,
                        'quick',
                        'complex'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1d',
                        12,
                        'simple',
                        'simple'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1d',
                        12,
                        'simple',
                        'complex'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1d',
                        12,
                        'quick',
                        'simple'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1d',
                        12,
                        'quick',
                        'complex'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1w',
                        12,
                        'simple',
                        'simple'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1w',
                        12,
                        'simple',
                        'complex'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1w',
                        12,
                        'quick',
                        'simple'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1w',
                        12,
                        'quick',
                        'complex'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1M',
                        12,
                        'simple',
                        'simple'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1M',
                        12,
                        'simple',
                        'complex'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1M',
                        12,
                        'quick',
                        'simple'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1M',
                        12,
                        'quick',
                        'complex'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1M',
                        5,
                        'simple',
                        'simple'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1M',
                        5,
                        'simple',
                        'complex'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1M',
                        5,
                        'quick',
                        'simple'
                    )
                );

                dispatch(new BinanceTestJob(
                        '1M',
                        5,
                        'quick',
                        'complex'
                    )
                );*/

        debug('Binance job is starting');

    }

    public function ema()
    {

        $result = Capital::simple(
            Strategy::emaSimple(
                $this->binance->getCandles('BTC/USDT', '1d'),
                100
            )
        );

        debug($result['final']);

    }

    public function coraWaveOld()
    {

        $pairs = BinancePair::all();
        $pairs = BinancePair::where('pair', 'BTC/USDT')->get();

        foreach ($pairs as $pair) {

            $result = Capital::simple(
                Strategy::coraWaveSimple(
                    $this->binance->getCandles($pair->pair, '1M'),
                    12
                )
            );

            if ($result['final'] != null) {
                debug($pair->pair);
                debug($result['indicators']);
            }

        }

    }

    public function loadCandles()
    {

        return $this->binance->loadCandles();

    }

    public function updateCandles()
    {

        return $this->binance->updateCandles('BTC/USDT');

    }

    public function allTickers()
    {

        $tickers = BinancePair::orderBy('pair')->get()->toArray();

        foreach ($tickers as $ticker) {

            debug($ticker['pair']);

        }

    }

    public function emaBtc()
    {

        $result = Capital::simple(
            Strategy::emaSimple(
                $this->binance->getCandles('BTC/USDT', '1d'),
                100
            )
        );

        debug($result);

    }

}
