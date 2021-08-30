<?php

namespace App\Src;

use App\Models\BinancePair;
use App\Src\Telegram;

class Binance
{
    private $exchange;
    private $limit = 800;

    public $binance_telegram_token;
    public $binance_chat_id;

    public function __construct()
    {
        $this->exchange = new \ccxt\binance();

        $this->binance_telegram_token = config('api.telegram_token_2');
        $this->binance_chat_id = config('api.chat_id_2');
    }

    public function getCandles($symbol, $timeframe)
    {

        if ($this->exchange->has['fetchOHLCV']) {

            usleep ($this->exchange->rateLimit * 10);

            try {

                $candles = $this->exchange->fetch_ohlcv($symbol, $timeframe, null, $this->limit);

            } catch (TIException $e) {

                debug('Can\'t get candles!!!');

                $telegram = new Telegram(
                    $this->binance_telegram_token,
                    $this->binance_chat_id
                );

                $telegram->send('Can\'t get candles!!!');

                die();

            }

            $this->proccessCandles($candles);

            return $candles;

        }

        return null;

    }

    public function getAllPairs()
    {
        return BinancePair::where('notify', true)->get();
    }

    private function proccessCandles(&$candles)
    {

        foreach ($candles as $key => $candle) {

            $candle['timestamp'] = $candle[0] / 1000;
            $candle['open'] = $candle[1];
            $candle['high'] = $candle[2];
            $candle['low'] = $candle[3];
            $candle['close'] = $candle[4];
            $candle['volume'] = $candle[5];

            unset($candle[0]);
            unset($candle[1]);
            unset($candle[2]);
            unset($candle[3]);
            unset($candle[4]);
            unset($candle[5]);

            $candles[$key] = $candle;

        }

    }

}
