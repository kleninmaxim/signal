<?php

namespace App\Http\Controllers;

use App\Jobs\TinkoffTestJob;
use App\Src\Math;
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

    public function volumeFiveMinute()
    {

        $this->tinkoff->telegram_token = config('api.telegram_token_rocket');

        $tickers = TinkoffTicker::where([
            ['name', 'NOT REGEXP', '^[а-яА-Я]'],
            ['type', 'Stock'],
            ['margin', true]
        ])->select(['figi', 'ticker'])->get()->toArray();

        foreach ($tickers as $ticker) {

            $candles = $this->tinkoff->getFiveMinuteCandle($ticker['figi']);

            $message = Strategy::fiveMinuteVolume($candles);

            if ($message) {

                $this->tinkoff->sendTelegramMessage(
                    'Strategy: five minute volume' . "\n" .
                    'Ticker is: ' . $ticker['ticker'] . "\n" .
                    $message . "\n"
                );

            }

        }

    }

    public function test()
    {

        $tickers = TinkoffTicker::where([
            ['name', 'NOT REGEXP', '^[а-яА-Я]'],
            ['type', 'Stock']
        ])->skip(0)->take(100)->get();

        $output = [];

        $sum = 0;
        $sum_apy = 0;
        $real_apy_sum = 0;
        $day = 1;

        $count = count($tickers);

        foreach ($tickers as $ticker) {

            $result = Capital::simple(
                Strategy::coraWaveSimple(
                    $this->tinkoff->getCandles($ticker->ticker, '1w'),
                    5
                )
            );

            if ($result['indicators'] != null) {

                $sampling = array_column($result['indicators'], 'profit_percentage');

                $output = array_merge($output, $sampling);

                $sum += $result['final']['profit_percentage_sum'];
                $sum_apy += $result['final']['profit_percentage_apy_sum'];
                $day = max($day, $result['final']['days']);

                if ($result['final']['days'] >= 365) {

                    $real_apy = (pow(($result['final']['profit_percentage_sum'] / 100 + 1), 365 / $result['final']['days']) - 1) * 100;

                } else {

                    $real_apy = 0;

                }

                $real_apy_sum += $real_apy;

            }

        }

        debug(Math::statisticAnalyse($output));

        debug(
            'I: ' . $sum / $count . "\n" .
            'Days: ' . $day . "\n" .
            'APY: ' . $sum / $count * 365 / $day . "\n" .
            'Sum APY: ' . $sum_apy / $count . "\n" .
            'Real APY: ' . $real_apy_sum / $count . "\n\n"
        );

        /*dispatch(new TinkoffTestJob(
                '1d',
                12,
                'simple',
                'simple'
            )
        );

        dispatch(new TinkoffTestJob(
                '1d',
                12,
                'simple',
                'complex'
            )
        );

        dispatch(new TinkoffTestJob(
                '1d',
                12,
                'quick',
                'simple'
            )
        );

        dispatch(new TinkoffTestJob(
                '1d',
                12,
                'quick',
                'complex'
            )
        );

        dispatch(new TinkoffTestJob(
                '1w',
                12,
                'simple',
                'simple'
            )
        );

        dispatch(new TinkoffTestJob(
                '1w',
                12,
                'simple',
                'complex'
            )
        );

        dispatch(new TinkoffTestJob(
                '1w',
                12,
                'quick',
                'simple'
            )
        );

        dispatch(new TinkoffTestJob(
                '1w',
                12,
                'quick',
                'complex'
            )
        );

        dispatch(new TinkoffTestJob(
                '1M',
                12,
                'simple',
                'simple'
            )
        );

        dispatch(new TinkoffTestJob(
                '1M',
                12,
                'simple',
                'complex'
            )
        );

        dispatch(new TinkoffTestJob(
                '1M',
                12,
                'quick',
                'simple'
            )
        );

        dispatch(new TinkoffTestJob(
                '1M',
                12,
                'quick',
                'complex'
            )
        );

        dispatch(new TinkoffTestJob(
                '1M',
                5,
                'simple',
                'simple'
            )
        );

                dispatch(new TinkoffTestJob(
                        '1M',
                        5,
                        'simple',
                        'complex'
                    )
                );

                dispatch(new TinkoffTestJob(
                        '1M',
                        5,
                        'quick',
                        'simple'
                    )
                );

                dispatch(new TinkoffTestJob(
                        '1M',
                        5,
                        'quick',
                        'complex'
                    )
                );*/

        debug('Tinkoff job is starting');

    }

    public function coraWave()
    {
        //$tickers = TinkoffTicker::all();
        $tickers = TinkoffTicker::skip(0)->take(100)->get();

        $sum = 0;
        $day = 0;

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
