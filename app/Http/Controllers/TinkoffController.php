<?php

namespace App\Http\Controllers;

use App\Src\Tinkoff;
use Illuminate\Http\Request;
use App\Src\Strategy;
use App\Src\Telegram;

class TinkoffController extends Controller
{
    public $tinkoff;

    public function __construct()
    {
        $this->tinkoff = new Tinkoff();
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

        $this->tinkoff->addNewTicker($request->ticker);

        return redirect()->back()->with('add', 'Ticker ' . $request->ticker . ' was added');

    }

    public function loadCandles()
    {
        return $this->tinkoff->loadCandles();
    }

    public function loadDayWeekMonthCandles()
    {
        return $this->tinkoff->loadDayWeekMonthCandles();
    }

    public function updateAllCandles()
    {
        $tickers = $this->tinkoff->getAllTickers();

        foreach ($tickers as $ticker) {

            $this->tinkoff->updateCandles($ticker);

        }

        return true;
    }

    public function notifyHourStrategies()
    {

        return $this->macd('1h');

    }

    public function notifyFourHourStrategies()
    {

        return $this->macd('4h');

    }

    public function notifyDayStrategies()
    {

        return $this->macd('1d');

    }

    public function notifyHourAfterStrategies()
    {

        return $this->macd('1h', true);

    }

    public function notifyFourHourAfterStrategies()
    {

        return $this->macd('4h', true);

    }

/*    public function notifyDayAfterStrategies()
    {

        return $this->macd('1d', true);

    }*/

    private function macd($timeframe, $after = false)
    {

        $tickers = $this->tinkoff->getAllTickers();

        $options = Strategy::getOptions('tinkoff', 'MACD', $timeframe);

        foreach ($tickers as $ticker) {

            if ($ticker->notify) {

                $candles = $this->tinkoff->getCandles($ticker, $timeframe);

                foreach ($options as $option) {

                    $signal = Strategy::proccessMacd(
                        $candles,
                        $option['fast'],
                        $option['slow'],
                        $option['signal'],
                        $after
                    );

                    if ($this->sendMessageOrNot($signal, $ticker)) {

                        $message =
                            'Tinkoff' . "\n" .
                            'MACD (' . $option['fast'] . ', ' . $option['slow'] . ').' . "\n" .
                            'Ticker: ' . $ticker->ticker . '.' . "\n" .
                            'Timeframe: ' . $timeframe . '.' . "\n" .
                            'Signal: ' . $signal;

                        $telegram = new Telegram(
                            $this->tinkoff->tinkoff_telegram_token,
                            $this->tinkoff->tinkoff_chat_id
                        );

                        $telegram->send($message);

                    }

                }

            }

        }

        return true;

    }

    private function sendMessageOrNot($signal, $ticker)
    {

        if ($signal == 'SHORT' && $ticker->short) {

            return true;

        } elseif ($signal == 'LONG') return true;

        return false;

    }

}
