<?php

namespace App\Http\Controllers;

use App\Src\Binance;
use App\Src\StrategyTest;

class BinanceController extends Controller
{

    private  $binance;

    public function __construct()
    {
        $this->binance = new Binance();
    }

    public function coraWave()
    {
        $result = StrategyTest::capitalJustAction(
            StrategyTest::proccessCoraWaveSimple(
                (new Binance())->getCandles('BTC/USDT', '1w'),
                12
            )
        );

        debug($result, true);

        return $result;

    }

    public function myStrategy()
    {

        (new StrategyTest())->testStrategyBinance();

    }

    public function test()
    {

        (new StrategyTest())->test();

    }

    public function testEmaBinance()
    {

        (new StrategyTest())->testEmaBinance();

    }

    public function loadCandles()
    {

        return $this->binance->loadCandles();

    }

}
