<?php

namespace App\Http\Controllers;

use App\Src\Binance;
use App\Src\CapitalRule;
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

        $result = CapitalRule::simple(
            Strategy::emaSimple(
                $this->binance->getCandles('BTC/USDT', '1d'),
                100
            )
        );

        debug($result);

    }

    public function coraWave()
    {

        $pairs = BinancePair::all();

        foreach ($pairs as $pair) {

            $result = CapitalRule::simple(
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
