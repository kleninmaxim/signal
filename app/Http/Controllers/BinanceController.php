<?php

namespace App\Http\Controllers;

use App\Src\Binance;
use App\Src\Capital;
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

        $result = Capital::complex(
            Strategy::emaSimple(
                $this->binance->getCandles('BTC/USDT', '5m'),
                1000
            )
        );

        debug($result);

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
