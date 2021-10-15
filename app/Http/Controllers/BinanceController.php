<?php

namespace App\Http\Controllers;

use App\Src\Binance;
use App\Src\StrategyTest;

use App\Models\BinancePair;

class BinanceController extends Controller
{

    private  $binance;

    public function __construct()
    {
        $this->binance = new Binance();
    }

    public function coraWave()
    {

        $pairs = BinancePair::all();

        foreach ($pairs as $pair) {

            $result = StrategyTest::capitalJustAction(
                StrategyTest::proccessCoraWaveSimple(
                    $this->binance->getCandles($pair->pair, '1M'),
                    12
                )
            );

            if ($result['profit_percentage_sum'] != 0) {
                debug($pair->pair);
                debug($result);
            }

        }

    }

    public function loadCandles()
    {

        return $this->binance->loadCandles();

    }

}
