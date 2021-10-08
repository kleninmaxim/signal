<?php

namespace App\Http\Controllers;

use App\Models\TinkoffTicker;
use App\Src\StrategyTest;
use App\Src\Tinkoff;
use Illuminate\Http\Request;

class TinkoffController extends Controller
{
    public $tinkoff;

    public function __construct()
    {
        $this->tinkoff = new Tinkoff();
    }

    public function coraWave()
    {
        $tickers = TinkoffTicker::all();

        foreach ($tickers as $ticker) {

            $result = StrategyTest::capitalJustAction(
                StrategyTest::proccessCoraWaveSimple(
                    (new Tinkoff())->getCandles($ticker->ticker, '1M'),
                    12
                )
            );

            debug($ticker->ticker);
            debug($result);
        }

        //return $result;

    }

    public function test()
    {

        return $this->tinkoff->test();

    }

    public function addNewTicker(Request $request)
    {

        $request->validate([
            'ticker' => 'required|max:255'
        ]);

        $added = $this->tinkoff->addNewTicker($request->ticker);

        return redirect()->back()->with(
            'add',
            'Ticker ' . $request->ticker . ($added ? ' was added' : ' already exists')
        );

    }

    public function loadHourCandles()
    {
        return $this->tinkoff->loadHourCandles();
    }

    public function loadDayWeekMonthCandles()
    {
        return $this->tinkoff->loadDayWeekMonthCandles();
    }
}
