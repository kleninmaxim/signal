<?php

namespace App\Src;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

use App\Traits\TelegramSend;

use App\Models\BinancePair;
use App\Models\BinanceFiveMinuteCandle;
use App\Models\BinanceFifteenMinuteCandle;
use App\Models\BinanceThirtyMinuteCandle;
use App\Models\BinanceHourCandle;
use App\Models\BinanceFourHourCandle;
use App\Models\BinanceDayCandle;
use App\Models\BinanceWeekCandle;
use App\Models\BinanceMonthCandle;

class Binance
{
    use TelegramSend;

    private $limit = 1000;
    private $exchange;

    public $telegram_token;
    public $chat_id;

    public function __construct()
    {
        $this->exchange = new \ccxt\binance();

        $this->telegram_token = config('api.telegram_token_2');
        $this->chat_id = config('api.chat_id_2');
    }

    public function loadCandles()
    {
        $notify = false;

        $pair = BinancePair::where('notify', $notify)->first();

        if (!empty($pair)) {

            $pair->notify = !$notify;

            $pair->save();

/*            $this->recordData($pair, '5m', $notify);
            $this->recordData($pair, '15m', $notify);
            $this->recordData($pair, '30m', $notify);*/
/*            $this->recordData($pair, '1h', $notify);
            $this->recordData($pair, '4h', $notify);
            $this->recordData($pair, '1d', $notify);*/
/*            $this->recordData($pair, '1w', $notify);
            $this->recordData($pair, '1M', $notify);*/

        }

        return true;

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

    private function proccessCandles(&$candles)
    {

        foreach ($candles as $key => $candle) {

            $candle['timestamp'] = $candle[0] / 1000;
            $candle['time_start'] = $candle[0] / 1000;
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

    private function recordData($pair, $timeframe, $notify)
    {

        $timae_start = 1503003600000;

        do {

            try {

                $pair_str = str_replace('/', '', $pair->pair);

                $api_candles = Http::get('https://api.binance.com/api/v3/klines', [
                    'symbol' => $pair_str,
                    'interval' => $timeframe,
                    'startTime' => $timae_start,
                    'limit' => $this->limit,
                ])->collect()->toArray();

                $last = array_pop($api_candles);

                $timae_start = $last[0];

            } catch (TIException $e) {

                $this->sendTelegramMessage('Can\'t get candles!!! With symbol: ' . $pair);

                $this->deleteCandles($pair->id, $timeframe);

                $pair->notify = $notify;

                $pair->save();

                return false;

            }

            $this->recordCandles($pair->id, $timeframe, $api_candles);

        } while (!empty($api_candles));

        return true;

    }

    private function recordCandles($id, $timeframe, $api_candles)
    {

        foreach ($api_candles as $candle) {

            $array = [
                'binance_pair_id' => $id,
                'open' => $candle[1],
                'high' => $candle[2],
                'low' => $candle[3],
                'close' => $candle[4],
                'volume' => $candle[5],
                'time_start' => Carbon::createFromTimestamp($candle[0] / 1000)->toDateTimeString()
            ];

            if ($timeframe == '5m') BinanceFiveMinuteCandle::create($array);
            elseif ($timeframe == '15m') BinanceFifteenMinuteCandle::create($array);
            elseif ($timeframe == '30m') BinanceThirtyMinuteCandle::create($array);
            elseif ($timeframe == '1h') BinanceHourCandle::create($array);
            elseif ($timeframe == '4h') BinanceFourHourCandle::create($array);
            elseif ($timeframe == '1d') BinanceDayCandle::create($array);
            elseif ($timeframe == '1w') BinanceWeekCandle::create($array);
            elseif ($timeframe == '1M') BinanceMonthCandle::create($array);

        }

    }

    private function deleteCandles($id, $timeframe)
    {

        if ($timeframe == '5m') BinanceFiveMinuteCandle::where('binance_pair_id', $id)->delete();
        elseif ($timeframe == '15m') BinanceFifteenMinuteCandle::where('binance_pair_id', $id)->delete();
        elseif ($timeframe == '30m') BinanceThirtyMinuteCandle::where('binance_pair_id', $id)->delete();
        elseif ($timeframe == '1h') BinanceHourCandle::where('binance_pair_id', $id)->delete();
        elseif ($timeframe == '4h') BinanceFourHourCandle::where('binance_pair_id', $id)->delete();
        elseif ($timeframe == '1d') BinanceDayCandle::where('binance_pair_id', $id)->delete();
        elseif ($timeframe == '1w') BinanceWeekCandle::where('binance_pair_id', $id)->delete();
        elseif ($timeframe == '1M') BinanceMonthCandle::where('binance_pair_id', $id)->delete();

    }

}
