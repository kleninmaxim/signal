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
    public $telegram_user_id;

    public function __construct()
    {
        $this->exchange = new \ccxt\binance();

        $this->telegram_token = config('api.telegram_token_binance');
        $this->telegram_user_id = config('api.telegram_user_id');
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

    public function updateCandles($pair)
    {

        $timeframe = '1M';

        $pair = BinancePair::where('pair', $pair)->first();

        $candle = BinancePair::where('pair', $pair->pair)->first()
            ->getCandles($timeframe)->orderByDesc('time_start')
            ->select('time_start')
            ->first()->toArray()['time_start'];

        $this->recordData($pair, $timeframe, true, (strtotime($candle) + 1) * 1000);

        return true;

    }

    public function getCandles($symbol, $timeframe, $desc = true)
    {

        $skip = 0;
        $take = 200000;

        if ($desc) {

            return array_reverse(
                BinancePair::where('pair', $symbol)->first()
                ->getCandles($timeframe)->orderByDesc('time_start')->skip($skip)->take($take)
                ->select('open', 'close', 'high', 'low', 'volume', 'time_start')
                ->get()->toArray()
            );

        }

        return BinancePair::where('pair', $symbol)->first()
            ->getCandles($timeframe)->skip($skip)->take($take)
            ->select('open', 'close', 'high', 'low', 'volume', 'time_start')
            ->get()->toArray();

    }

    public function getCandlesApiFormat($pair, $timeframe, $time_start)
    {

        $api_candles = $this->getCandlesApi($pair, $timeframe, $time_start);

        foreach ($api_candles as $key => $candle) {

            $candles[$key] = [
                'open' => $candle[1],
                'high' => $candle[2],
                'low' => $candle[3],
                'close' => $candle[4],
                'volume' => $candle[5],
                'time_start' => Carbon::createFromTimestamp($candle[0] / 1000)->toDateTimeString()
            ];

        }

        return $candles ?? [];

    }



    private function recordData($pair, $timeframe, $notify, $time_start = 1503003600000)
    {

        do {

            try {

                $pair_str = str_replace('/', '', $pair->pair);

                $api_candles = $this->getCandlesApi($pair_str, $timeframe, $time_start);

                $last = array_pop($api_candles);

                $time_start = $last[0];

            } catch (\Exception $e) {

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

    private function getCandlesApi($pair, $timeframe, $time_start)
    {

        return Http::get('https://api.binance.com/api/v3/klines', [
            'symbol' => $pair,
            'interval' => $timeframe,
            'startTime' => $time_start,
            'limit' => $this->limit,
        ])->collect()->toArray();

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
