<?php

namespace App\Src;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TIException;
use jamesRUS52\TinkoffInvest\TIIntervalEnum;
use jamesRUS52\TinkoffInvest\TISiteEnum;

use App\Src\StrategyTest;
use App\Traits\TelegramSend;

use App\Models\TinkoffTicker;
use App\Models\TinkoffHourCandle;
use App\Models\TinkoffFourHourCandle;
use App\Models\TinkoffDayCandle;
use App\Models\TinkoffWeekCandle;
use App\Models\TinkoffMonthCandle;

class Tinkoff
{
    use TelegramSend;

    private $client;
    private $interval = 120;

    public $telegram_token;
    public $chat_id;

    public function __construct()
    {

        $this->client = new TIClient(config('api.tinkoff_token'), TISiteEnum::EXCHANGE);

        $this->telegram_token = config('api.telegram_token_2');
        $this->chat_id = config('api.chat_id_2');

    }

    public function test()
    {

/*        $ticker_data = $this->client->getCurrencies();

        debug($ticker_data, true);*/

        $stategy_test = new StrategyTest();

        $stategy_test->testTinkoff();

        return true;

    }

    public function getCandles($ticker, $timeframe, $desc = true)
    {
        $skip = 0;
        $take = 50000;

        if ($desc) {

            return array_reverse(
                TinkoffTicker::where('ticker', $ticker)->first()
                    ->getCandles($timeframe)->orderByDesc('time_start')->skip($skip)->take($take)
                    ->select('open', 'close', 'high', 'low', 'volume', 'time_start')
                    ->get()->toArray()
            );

        }

        return TinkoffTicker::where('ticker', $ticker)->first()
            ->getCandles($timeframe)->skip($skip)->take($take)
            ->select('open', 'close', 'high', 'low', 'volume', 'time_start')
            ->get()->toArray();
    }

    public function addNewTicker($ticker)
    {
        $ticker_data = TinkoffTicker::where('ticker', $ticker)->first();

        if (empty($ticker_data)) {

            DB::table('tinkoff_tikers_queue')->insertOrIgnore([
                'ticker' => $ticker
            ]);

            return true;

        }

        return false;
    }

    public function loadDayWeekMonthCandles()
    {

        $ticker = DB::table('tinkoff_tikers_queue')->select('ticker')->first();

        if (!empty($ticker)) {

            $this->deleteTickerQueue($ticker->ticker);

            try {

                $ticker_data = $this->client->getInstrumentByTicker($ticker->ticker);

            } catch (TIException $e) {

                $this->deleteTickerQueue($ticker->ticker);

                $this->sendTelegramMessage('No such ticker: ' . $ticker->ticker);

                return false;

            }


            try {

                $day_candles = $this->getCandlesAPI($ticker_data->getFigi(), TIIntervalEnum::DAY, 100);
                $week_candles = $this->getCandlesAPI($ticker_data->getFigi(), TIIntervalEnum::WEEK, 100);
                $month_candles = $this->getCandlesAPI($ticker_data->getFigi(), TIIntervalEnum::MONTH, 100);

            } catch (TIException $e) {

                return false;

            }

            $id = $this->insertTicker($ticker_data);
            $this->insertDayCandle($day_candles, $id);
            $this->insertWeekCandle($week_candles, $id);
            $this->insertMonthCandle($month_candles, $id);

            return true;

        }

        return false;

    }

    public function loadHourCandles()
    {

        $ticker = DB::table('tinkoff_tikers_queue')->select('ticker')->first();

        if (!empty($ticker)) {

            try {

                $ticker_data = $this->client->getInstrumentByTicker($ticker->ticker);

            } catch (TIException $e) {

                $this->deleteTickerQueue($ticker->ticker);

                $this->sendTelegramMessage('Can\'t get ticker!!! Ticker is: ' . $ticker->ticker);

                return false;

            }

            $hour_candles = $this->getHourCandles($ticker_data->getFigi());

            $this->formatCandles($hour_candles);

            if ($this->checkCurrentTimeWithCandle($hour_candles)) array_pop($hour_candles);

            $id = $this->insertTicker($ticker_data);
            $this->deleteTickerQueue($ticker->ticker);
            $this->insertHourCandle($hour_candles, $id);

            return true;

        }

        return false;

    }

    private function getHourCandles($figi, $interval = null)
    {

        $interval = $interval ?? $this->interval;

        for ($i = 1; $i < $interval; $i++) {

            $from = new \DateTime();
            $to = new \DateTime();

            $from->sub(new \DateInterval('P' . 7 * $i . 'D'));
            $to->sub(new \DateInterval('P' . 7 * ($i - 1) . 'D'));

            try {

                $candles = array_reverse($this->client->getHistoryCandles($figi, $from, $to, TIIntervalEnum::HOUR));

            } catch (TIException $e) {

                $this->sendTelegramMessage('Can\'t get candles!!! FIGI is: ' . $figi);

                die();

            }

            if (empty($candles)) break;

            foreach ($candles as $candle) {

                $hour_candles_pre['open'] = $candle->getOpen();
                $hour_candles_pre['close'] = $candle->getClose();
                $hour_candles_pre['high'] = $candle->getHigh();
                $hour_candles_pre['low'] = $candle->getLow();
                $hour_candles_pre['volume'] = $candle->getVolume();
                $hour_candles_pre['time_start'] = Carbon::createFromTimestamp($candle->getTime()->getTimestamp(), 'Europe/Moscow')->toDateTimeString();

                $hour_candles[] = $hour_candles_pre;

            }

        }

        return $hour_candles ?? [];

    }

    private function getCandlesAPI($figi, $day = TIIntervalEnum::DAY, $interval = null)
    {

        $interval = $interval ?? $this->interval;

        for ($i = 1; $i < $interval; $i++) {

            $from = new \DateTime();
            $to = new \DateTime();

            $from->sub(new \DateInterval('P' . 365 * $i . 'D'));
            $to->sub(new \DateInterval('P' . 365 * ($i - 1) . 'D'));

            try {

                $candles = array_reverse($this->client->getHistoryCandles($figi, $from, $to, $day));

            } catch (TIException $e) {

                $this->sendTelegramMessage('Can\'t get candles!!! FIGI is: ' . $figi);

                die();

            }

            if (empty($candles)) break;

            foreach ($candles as $candle) {

                $day_candles_pre['open'] = $candle->getOpen();
                $day_candles_pre['close'] = $candle->getClose();
                $day_candles_pre['high'] = $candle->getHigh();
                $day_candles_pre['low'] = $candle->getLow();
                $day_candles_pre['volume'] = $candle->getVolume();
                $day_candles_pre['time_start'] = Carbon::createFromTimestamp($candle->getTime()->getTimestamp(), 'Europe/Moscow')->toDateTimeString();

                $day_candles[] = $day_candles_pre;

            }

        }

        return $day_candles ?? [];

    }

    private function insertTicker($ticker_data)
    {
        $tinkoff = TinkoffTicker::create([
            'figi' => $ticker_data->getFigi(),
            'ticker' => $ticker_data->getTicker(),
            'name' => $ticker_data->getName(),
            'type' => $ticker_data->getType()
        ]);

        return $tinkoff->id;
    }

    private function insertHourCandle($hour_candles, $id)
    {
        foreach ($hour_candles as $hour_candle) {
            TinkoffHourCandle::create([
                'tinkoff_ticker_id' => $id,
                'open' => $hour_candle['open'],
                'close' => $hour_candle['close'],
                'high' => $hour_candle['high'],
                'low' => $hour_candle['low'],
                'volume' => $hour_candle['volume'],
                'time_start' => $hour_candle['time_start']
            ]);
        }
    }

    private function insertDayCandle($day_candles, $id)
    {
        foreach ($day_candles as $day_candle) {
            TinkoffDayCandle::create([
                'tinkoff_ticker_id' => $id,
                'open' => $day_candle['open'],
                'close' => $day_candle['close'],
                'high' => $day_candle['high'],
                'low' => $day_candle['low'],
                'volume' => $day_candle['volume'],
                'time_start' => $day_candle['time_start']
            ]);
        }
    }

    private function insertWeekCandle($week_candles, $id)
    {
        foreach ($week_candles as $week_candle) {
            TinkoffWeekCandle::create([
                'tinkoff_ticker_id' => $id,
                'open' => $week_candle['open'],
                'close' => $week_candle['close'],
                'high' => $week_candle['high'],
                'low' => $week_candle['low'],
                'volume' => $week_candle['volume'],
                'time_start' => $week_candle['time_start']
            ]);
        }
    }

    private function insertMonthCandle($month_candles, $id)
    {
        foreach ($month_candles as $month_candle) {
            TinkoffMonthCandle::create([
                'tinkoff_ticker_id' => $id,
                'open' => $month_candle['open'],
                'close' => $month_candle['close'],
                'high' => $month_candle['high'],
                'low' => $month_candle['low'],
                'volume' => $month_candle['volume'],
                'time_start' => $month_candle['time_start']
            ]);
        }
    }

    private function checkCurrentTimeWithCandle($candles)
    {

        $last_candle = array_pop($candles);

        return Carbon::now()->diffInHours(Carbon::parse($last_candle['time_start']), false) == 0;

    }

    private function formatCandles(&$hour_candles)
    {

        $hour_candles = array_reverse($hour_candles);

        $first_day = Carbon::parse($hour_candles[0]['time_start'])->day;

        foreach ($hour_candles as $key => $my_candle) {

            $day = Carbon::parse($my_candle['time_start'])->day;

            if ($day == $first_day) unset($hour_candles[$key]);
            else if ($first_day == $day - 1) {

                $hour = Carbon::parse($my_candle['time_start'])->hour;

                if ($hour <= 5) unset($hour_candles[$key]);
            } else break;

        }

    }

    private function deleteTickerQueue($ticker)
    {
        DB::table('tinkoff_tikers_queue')->where('ticker', $ticker)->delete();
    }

}
