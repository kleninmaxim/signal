<?php

namespace App\Http\Controllers;

use App\Src\Binance;
use App\Src\Strategy;
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

        return (new StrategyTest())->coraWave();

    }

    public function myStrategy()
    {

        return (new StrategyTest())->testStrategyBinance();

    }

    public function test()
    {

        return (new StrategyTest())->test();

    }

    public function testEmaBinance()
    {

        return (new StrategyTest())->testEmaBinance();

    }

    public function loadCandles()
    {

        return $this->binance->loadCandles();

    }

}
