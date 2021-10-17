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

        foreach ($pairs as $pair) {

            $result = Capital::simple(
                Strategy::coraWaveSimple(
                    $this->binance->getCandles($pair->pair, '1M'),
                    12
                )
            );

            if ($result['final'] != null) {
                debug($pair->pair);
                debug($result['final']);
            }

        }

    }

    public function loadCandles()
    {

        return $this->binance->loadCandles();

    }

}
