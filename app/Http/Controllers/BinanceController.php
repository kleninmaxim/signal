<?php

namespace App\Http\Controllers;

use App\Src\Binance;
use App\Src\Strategy;
use App\Src\Telegram;

class BinanceController extends Controller
{

    private  $binance;

    public function __construct()
    {
        $this->binance = new Binance();
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

    public function notifyDayAfterStrategies()
    {

        return $this->macd('1d', true);

    }

    private function macd($timeframe, $after = false)
    {

        $pairs = $this->binance->getAllPairs();

        $options = Strategy::getOptions('binance', 'MACD', $timeframe);

        foreach ($pairs as $pair) {

            $candles = $this->binance->getCandles($pair->pair, $timeframe);

            foreach ($options as $option) {

                $signal = Strategy::proccessMacd(
                    $candles,
                    $option['fast'],
                    $option['slow'],
                    $option['signal'],
                    $after
                );

                debug($signal, true);

                if ($this->sendMessageOrNot($signal, $pair)) {

                    $message =
                        'BINANCE' . "\n" .
                        'MACD (' . $option['fast'] . ', ' . $option['slow'] . ').' . "\n" .
                        'Pair: ' . $pair->pair . '.' . "\n" .
                        'Timeframe: ' . $timeframe . '.' . "\n" .
                        'Signal: ' . $signal;

                    $telegram = new Telegram(
                        $this->binance->binance_telegram_token,
                        $this->binance->binance_chat_id
                    );

                    $telegram->send($message);

                }

            }

        }

        return true;

    }

    private function sendMessageOrNot($signal, $pair)
    {

        if ($signal == 'SHORT' && $pair->short) {

            return true;

        } elseif ($signal == 'LONG') return true;

        return false;

    }

}
