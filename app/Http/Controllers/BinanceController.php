<?php

namespace App\Http\Controllers;

use App\Src\Binance;
use App\Src\Strategy;
use App\Src\Telegram;
use App\Src\StrategyTest;
use Carbon\Carbon;
use App\Models\BinancePair;
use App\Models\BinanceFiveMinuteCandle;
use App\Models\BinanceFifteenMinuteCandle;
use App\Models\BinanceThirtyMinuteCandle;
use App\Models\BinanceHourCandle;
use App\Models\BinanceFourHourCandle;
use App\Models\BinanceDayCandle;
use App\Models\BinanceWeekCandle;
use App\Models\BinanceMonthCandle;
use Illuminate\Support\Facades\Http;

class BinanceController extends Controller
{

    private  $binance;

    public function __construct()
    {
        $this->binance = new Binance();
    }

    public function coraWave()
    {

        (new StrategyTest())->coraWave();

    }

    public function myStrategy()
    {

        $stategy_test = new StrategyTest();

        $stategy_test->testStrategyBinance();

    }

    public function test()
    {

        $stategy_test = new StrategyTest();

        $stategy_test->test();

    }

    public function testEmaBinance()
    {

        $stategy_test = new StrategyTest();

        $stategy_test->testEmaBinance();

    }

    public function loadCandles()
    {

        $pair = BinancePair::where('notify', false)->first();

        if (!empty($pair)) {

            $pair->notify = true;

            $pair->save();

/*            $this->recordData($pair, '5m');
            $this->recordData($pair, '15m');
            $this->recordData($pair, '30m');*/
/*            $this->recordData($pair, '1h');
            $this->recordData($pair, '4h');
            $this->recordData($pair, '1d');*/
            $this->recordData($pair, '1w');
            $this->recordData($pair, '1M');

        }

        return true;

    }

    private function recordData($pair, $timeframe)
    {
        //$exchange = new \ccxt\binance();

        //usleep((new \ccxt\binance())->rateLimit * 10);

        $timae_start = 1503003600000;

        do {

            try {

                //$api_candles = $exchange->fetch_ohlcv($pair->pair, $timeframe, $timae_start, 1000);

                $pair_str = str_replace('/', '', $pair->pair);

                $api_candles = Http::get('https://api.binance.com/api/v3/klines', [
                    'symbol' => $pair_str,
                    'interval' => $timeframe,
                    'startTime' => $timae_start,
                    'limit' => 1000,
                ])->collect()->toArray();

                $last = array_pop($api_candles);

                $timae_start = $last[0];

            } catch (TIException $e) {

                debug('Can\'t get candles!!!');

                if ($timeframe == '5m') BinanceFiveMinuteCandle::where('binance_pair_id',$pair->id)->delete();
                elseif ($timeframe == '15m') BinanceFifteenMinuteCandle::where('binance_pair_id',$pair->id)->delete();
                elseif ($timeframe == '30m') BinanceThirtyMinuteCandle::where('binance_pair_id',$pair->id)->delete();
                elseif ($timeframe == '1h') BinanceHourCandle::where('binance_pair_id',$pair->id)->delete();
                elseif ($timeframe == '4h') BinanceFourHourCandle::where('binance_pair_id',$pair->id)->delete();
                elseif ($timeframe == '1d') BinanceDayCandle::where('binance_pair_id',$pair->id)->delete();
                elseif ($timeframe == '1w') BinanceWeekCandle::where('binance_pair_id',$pair->id)->delete();
                elseif ($timeframe == '1M') BinanceMonthCandle::where('binance_pair_id',$pair->id)->delete();

                $pair->notify = false;

                $pair->save();

                die();

            }

            foreach ($api_candles as $candle) {

                $candle['time_start'] = Carbon::createFromTimestamp($candle[0] / 1000)->toDateTimeString();
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

                $array = [
                    'binance_pair_id' => $pair->id,
                    'open' => $candle['open'],
                    'close' => $candle['close'],
                    'high' => $candle['high'],
                    'low' => $candle['low'],
                    'volume' => $candle['volume'],
                    'time_start' => $candle['time_start']
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

        } while (!empty($api_candles));

        return true;

    }

    private function recordFiveMinuteCandles($pair, $timeframe)
    {

        $exchange = new \ccxt\binance();

        usleep($exchange->rateLimit * 10);

        $timae_start = 1503003600000;

        $api_candles = [1];

        while (!empty($api_candles)) {

            try {

                $api_candles = $exchange->fetch_ohlcv($pair->pair, $timeframe, $timae_start, 1000);

            } catch (TIException $e) {

                debug('Can\'t get candles!!!');

                BinanceFiveMinuteCandle::where('binance_pair_id',$pair->id)->delete();

                $pair->notify = false;

                $pair->save();

                die();

            }

            foreach ($api_candles as $candle) {

                $candle['time_start'] = Carbon::createFromTimestamp($candle[0] / 1000)->toDateTimeString();
                $candle['open'] = $candle[1];
                $candle['high'] = $candle[2];
                $candle['low'] = $candle[3];
                $candle['close'] = $candle[4];
                $candle['volume'] = $candle[5];

                $timae_start = $candle[0];

                unset($candle[0]);
                unset($candle[1]);
                unset($candle[2]);
                unset($candle[3]);
                unset($candle[4]);
                unset($candle[5]);

                BinanceFiveMinuteCandle::create([
                    'binance_pair_id' => $pair->id,
                    'open' => $candle['open'],
                    'close' => $candle['close'],
                    'high' => $candle['high'],
                    'low' => $candle['low'],
                    'volume' => $candle['volume'],
                    'time_start' => $candle['time_start']
                ]);

            }

        }

        return true;

    }

    private function recordFifteenMinuteCandles($pair, $timeframe)
    {

        $exchange = new \ccxt\binance();

        usleep($exchange->rateLimit * 10);

        $timae_start = 1503003600000;

        $api_candles = [1];

        while (!empty($api_candles)) {

            try {

                $api_candles = $exchange->fetch_ohlcv($pair->pair, $timeframe, $timae_start, 1000);

            } catch (TIException $e) {

                debug('Can\'t get candles!!!');

                BinanceFifteenMinuteCandle::where('binance_pair_id',$pair->id)->delete();

                $pair->notify = false;

                $pair->save();

                die();

            }

            foreach ($api_candles as $candle) {

                $candle['time_start'] = Carbon::createFromTimestamp($candle[0] / 1000)->toDateTimeString();
                $candle['open'] = $candle[1];
                $candle['high'] = $candle[2];
                $candle['low'] = $candle[3];
                $candle['close'] = $candle[4];
                $candle['volume'] = $candle[5];

                $timae_start = $candle[0];

                unset($candle[0]);
                unset($candle[1]);
                unset($candle[2]);
                unset($candle[3]);
                unset($candle[4]);
                unset($candle[5]);

                BinanceFifteenMinuteCandle::create([
                    'binance_pair_id' => $pair->id,
                    'open' => $candle['open'],
                    'close' => $candle['close'],
                    'high' => $candle['high'],
                    'low' => $candle['low'],
                    'volume' => $candle['volume'],
                    'time_start' => $candle['time_start']
                ]);

            }

        }

        return true;

    }

    private function recordThirtyMinuteCandles($pair, $timeframe)
    {

        $exchange = new \ccxt\binance();

        usleep($exchange->rateLimit * 10);

        $timae_start = 1503003600000;

        $api_candles = [1];

        while (!empty($api_candles)) {

            try {

                $api_candles = $exchange->fetch_ohlcv($pair->pair, $timeframe, $timae_start, 1000);

            } catch (TIException $e) {

                debug('Can\'t get candles!!!');

                BinanceThirtyMinuteCandle::where('binance_pair_id',$pair->id)->delete();

                $pair->notify = false;

                $pair->save();

                die();

            }

            foreach ($api_candles as $candle) {

                $candle['time_start'] = Carbon::createFromTimestamp($candle[0] / 1000)->toDateTimeString();
                $candle['open'] = $candle[1];
                $candle['high'] = $candle[2];
                $candle['low'] = $candle[3];
                $candle['close'] = $candle[4];
                $candle['volume'] = $candle[5];

                $timae_start = $candle[0];

                unset($candle[0]);
                unset($candle[1]);
                unset($candle[2]);
                unset($candle[3]);
                unset($candle[4]);
                unset($candle[5]);

                BinanceThirtyMinuteCandle::create([
                    'binance_pair_id' => $pair->id,
                    'open' => $candle['open'],
                    'close' => $candle['close'],
                    'high' => $candle['high'],
                    'low' => $candle['low'],
                    'volume' => $candle['volume'],
                    'time_start' => $candle['time_start']
                ]);

            }

        }

        return true;

    }

    private function getCandles($pair, $timeframe)
    {

        $exchange = new \ccxt\binance();

        usleep($exchange->rateLimit * 10);

        $timae_start = 1503003600000;

        $api_candles = [1];

        while (!empty($api_candles)) {

            try {

                $api_candles = $exchange->fetch_ohlcv($pair, $timeframe, $timae_start, 1000);

            } catch (TIException $e) {

                debug('Can\'t get candles!!!');

                die();

            }

            foreach ($api_candles as $candle) {

                $candle['time_start'] = Carbon::createFromTimestamp($candle[0] / 1000)->toDateTimeString();
                $candle['open'] = $candle[1];
                $candle['high'] = $candle[2];
                $candle['low'] = $candle[3];
                $candle['close'] = $candle[4];
                $candle['volume'] = $candle[5];

                $timae_start = $candle[0];

                unset($candle[0]);
                unset($candle[1]);
                unset($candle[2]);
                unset($candle[3]);
                unset($candle[4]);
                unset($candle[5]);

                $candles[] = $candle;

            }

        }

        return $candles ?? [];

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
