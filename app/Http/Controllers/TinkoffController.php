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
        $tickers = TinkoffTicker::skip(0)->take(100)->get();

        $sum = 0;
        $day = 0;

        foreach ($tickers as $ticker) {

            $result = Capital::complex(
                Strategy::coraWaveSimple(
                    $this->tinkoff->getCandles($ticker->ticker, '1M'),
                    12
                )
            );

            if ($result['final'] != null) {
                debug($ticker->ticker);
                debug($result['final']);
                $sum += $result['final']['profit_percentage_sum'];
                $day = max($day, $result['final']['days']);
            }

        }

        debug($sum / count($tickers));
        debug($day);
        debug($sum / count($tickers) * 365 / $day);

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

    public function allTickers()
    {

        $tickers = TinkoffTicker::orderBy('ticker')->get()->toArray();

        foreach ($tickers as $ticker) {

            debug($ticker['ticker']);

        }

    }

}
