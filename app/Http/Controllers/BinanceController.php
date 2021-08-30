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

        $length_emas = [
            ['fast' => 50, 'slow' => 100],
            ['fast' => 100, 'slow' => 200],
            ['fast' => 200, 'slow' => 400]
        ];

        foreach ($pairs as $pair) {

            $candles = $this->binance->getCandles($pair->pair, $timeframe);

            foreach ($length_emas as $length_ema) {

                $signal = Strategy::proccessMacd(
                    Strategy::macd(
                        $candles,
                        $length_ema['fast'],
                        $length_ema['slow']
                    ),
                    $after
                );

                if ($this->sendMessageOrNot($signal, $pair)) {

                    $message =
                        'BINANCE' . "\n" .
                        'MACD (' . $length_ema['fast'] . ', ' . $length_ema['slow'] . ').' . "\n" .
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
