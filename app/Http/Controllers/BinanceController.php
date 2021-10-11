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

    public function testCoraWaveOnMinutesCandles()
    {

        $pairs = BinancePair::all();

        foreach ($pairs as $pair) {

            $result = StrategyTest::capitalJustAction(
                StrategyTest::proccessCoraWaveSimple(
                    (new Binance())->getCandles($pair->pair, '1M'),
                    12
                )
            );

            if ($result['profit_percentage_sum'] != 0) {
                debug($pair->pair);
                debug($result);
            }

        }

    }

    public function coraWave()
    {
        $result = StrategyTest::capitalJustAction(
            StrategyTest::proccessCoraWaveSimple(
                (new Binance())->getCandles('BTC/USDT', '1M'),
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
