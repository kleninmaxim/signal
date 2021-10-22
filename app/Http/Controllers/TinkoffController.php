<?php

namespace App\Http\Controllers;

use App\Traits\Old\TinkoffControllerOld;
use Illuminate\Http\Request;

use App\Src\Capital;
use App\Src\Strategy;
use App\Src\Tinkoff;

use App\Models\TinkoffTicker;

class TinkoffController extends Controller
{

    use TinkoffControllerOld;

    public $tinkoff;

    public function __construct()
    {
        $this->tinkoff = new Tinkoff();
    }

    public function coraWave()
    {
        //$tickers = TinkoffTicker::all();
        $tickers = TinkoffTicker::skip(400)->take(100)->get();

        foreach ($tickers as $ticker) {

            $result = Capital::simple(
                Strategy::coraWaveSimple(
                    $this->tinkoff->getCandles($ticker->ticker, '1M'),
                    12
                )
            );

            if ($result['final'] != null) {
                debug($ticker->ticker);
                debug($result['final']);
            }

        }

        //return $result;

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
