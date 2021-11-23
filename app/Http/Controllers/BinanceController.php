<?php

namespace App\Http\Controllers;

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

    public function testFinalStrategy()
    {
        // добавь медиану, квантиль, мода
        // сделать дерево решений, добавить стратегию, трейдинг активами.

        $pairs = BinancePair::where('pair', 'BTC/USDT')->get()->toArray();
        $pairs = BinancePair::where('id', '<=', 48)->get()->toArray();

        foreach ($pairs as $pair) {

            $data = Strategy::finalSimple(
                $this->binance->getCandles($pair['pair'], '1M'),
                12,
                5
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
                //debug($data);

                debug($sequence_of_negative);
                debug($sequence_of_positive);
                debug(Math::change($p, $p + $n, 4) * 100);
                debug($p);
                debug($n);
                debug(array_sum($data));
                debug(($sum - 1) * 100);

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

    public function coraWave()
    {

        $pairs = BinancePair::all();
        $pairs = BinancePair::where('pair', 'BTC/USDT')->get();

        foreach ($pairs as $pair) {

            $result = Capital::simple(
                Strategy::coraWaveSimple(
                    $this->binance->getCandles($pair->pair, '5m'),
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
