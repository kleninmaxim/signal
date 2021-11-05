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

    public function test()
    {

        /*        $sampling = [1, 2, 3, 4, 5, 6, 7];

                $sampling = [
                    133.5, 141.5, 144.0, 137.5, 142.0, 139.0, 142.5, 141.5, 145.5, 140.5,
                    139.0, 141.0, 144.5, 139.0, 137.0, 142.5, 134.5, 143.5, 136.0, 143.5,
                    138.5, 139.5, 137.0, 141.0, 144.0, 140.5, 138.5, 147.0, 141.0, 140.0,
                    139.0, 139.5, 141.5, 138.5, 139.5, 136.5, 139.5, 135.0, 140.5, 142.0,
                    140.0, 139.5, 139.5, 140.0, 145.0, 139.0, 140.0, 141.5, 138.0, 140.5
                ];

                $sampling = [
                    20.3, 15.4, 17.2, 19.2, 23.1, 18.1, 21.9, 15.3, 16.8, 13.2,
                    20.4, 16.5, 19.7, 20.5, 14.3, 20.1, 16.8, 14.7, 20.8, 19.5,
                    15.4, 19.3, 17.8, 16.2, 15.7, 22.8, 21.9, 12.5, 10.1, 21.1
                ];

        debug(Math::staticAnalyse($sampling));*/

        $sampling = [
            -7.81, -2.43, 7.34, 28.06, -8.87, 20.51, -8.09, 23.77, 71.26, 4.26, -8.35, 1.55, -5.17, -15.94, -8.39, 26.04,
            1.4, -10.53,
            3.47,
            -8.57,
            -2.48,
            -0.48,
            7.7,
            -2.96,
            -3.95,
            -1.56,
            -6.45,
            3.38,
            -2.59,
            -9.94,
            -1.46,
            28.66,
            -0.93,
            -0.2,
            5.33,
            -3.68,
            1.38,
            -1.16,
            -0.07,
            -1.98,
            -0.22,
            -8.29,
            -8.93,
            -1.53,
            6.75,
            0.21,
            -2.27,
            -10.07,
            -1.46,
            -0.53,
            0.03,
            -1.36,
            3.46,
            -0.4,
            0.25,
            1.45,
            24.25,
            0.89,
            36.63,
            -6.38,
            3.83,
            -6.99,
            -4.42,
            50.96,
            -5.72,
            -0.56,
            -1.56,
            12.19,
            -7.08,
            -6.28,
            7.52,
            -1.57,
            -1.87,
            1.61,
            -9.2,
            5.77,
            -0.79,
            -1.57,
            -0.68,
            0.76,
            -1.65,
            17.52,
            8.32,
            -1.02,
            3.04,
            -6.25,
            -9.89,
            3.19,
            7,
            -3.87,
            18.57,
            -3.31,
            2.17,
            5.02,
            0.47,
            -5.13,
            -2.26,
            -4.02,
            -1.15,
            -0.6,
            0.14,
            21.84,
            -3,
            -0.05,
            -3.66,
            -0.67,
            0.78,
            -0.63,
            -1.17,
            3.6,
            38.72,
            2.61,
            5.29,
            -5.35,
            26.59,
            52.39,
            -8.05,
            -1.18,
            0.66,
            -2.12,
            52.5,
            -1.68,
            9.09,
            -0.51,
            2.21,
            -3.51,
            5.49,
            -1.48,
            -1.02,
            -6.94,
            -3.22,
            1.89,
            -6.86,
            -3.45,
            -3.02,
            -4.46,
            21.25,
            8.65,
            -6.49,
            -3.34,
            -3.91,
            -4.84,
            -8.69,
            -4.89,
        ];

        debug(Math::staticAnalyse($sampling));


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
