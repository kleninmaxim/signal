<?php

namespace App\Http\Controllers;

use App\Src\Binance;
use App\Src\Strategy;
use App\Src\Telegram;
use App\Src\StrategyTest;
use Carbon\Carbon;
use App\Models\BinancePair;
use App\Models\BinanceFifteenMinuteCandle;
use App\Models\BinanceFiveMinuteCandle;
use App\Models\BinanceThirtyMinuteCandle;

class BinanceController extends Controller
{

    private  $binance;

    public function __construct()
    {
        $this->binance = new Binance();
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

            $this->recordFiveMinuteCandles($pair, '5m');
            $this->recordFifteenMinuteCandles($pair, '15m');
            $this->recordThirtyMinuteCandles($pair, '30m');

        }

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
