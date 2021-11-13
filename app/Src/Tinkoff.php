<?php

namespace App\Src;

use App\Traits\Old\TinkoffOld;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TIException;
use jamesRUS52\TinkoffInvest\TIIntervalEnum;
use jamesRUS52\TinkoffInvest\TISiteEnum;

use App\Traits\TelegramSend;
use App\Traits\SqlTinkoff;

use App\Models\TinkoffTicker;

class Tinkoff
{
    use TelegramSend, SqlTinkoff, TinkoffOld;

    private $client;
    private $interval = 120;

    public $telegram_token;
    public $telegram_user_id;

    public function __construct()
    {

        $this->client = new TIClient(config('api.tinkoff_token'), TISiteEnum::EXCHANGE);

        $this->telegram_token = config('api.telegram_token_tinkoff');
        $this->telegram_user_id = config('api.telegram_user_id');

    }

    public function getFiveMinuteCandle($figi, $interval = 2)
    {

        $from = new \DateTime();
        $to = new \DateTime();

        $from->sub(new \DateInterval('PT' . $interval . 'H'));

        try {

            usleep(200000); //0.2 секунды

            $candles = array_reverse($this->client->getHistoryCandles($figi, $from, $to, TIIntervalEnum::MIN5));

        } catch (TIException $e) {

            $this->sendTelegramMessage('Can\'t get candles!!! FIGI is: ' . $figi);

            return [];

        }

        foreach ($candles as $candle) {

            $five_candles[] = [
                'open' => $candle->getOpen(),
                'close' => $candle->getClose(),
                'high' => $candle->getHigh(),
                'low' => $candle->getLow(),
                'volume' => $candle->getVolume(),
                'time_start' => Carbon::createFromTimestamp(
                    $candle->getTime()->getTimestamp(),
                    'Europe/Moscow'
                )->toDateTimeString(),
            ];

        }

        return $five_candles ?? [];

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

                $hour_candles[] = [
                    'open' => $candle->getOpen(),
                    'close' => $candle->getClose(),
                    'high' => $candle->getHigh(),
                    'low' => $candle->getLow(),
                    'volume' => $candle->getVolume(),
                    'time_start' => Carbon::createFromTimestamp(
                        $candle->getTime()->getTimestamp(),
                        'Europe/Moscow'
                    )->toDateTimeString(),
                ];

            }

        }

        return $hour_candles ?? [];

    }

    public function getCandlesAPI($figi, $day = TIIntervalEnum::DAY, $interval = null, $number = 365)
    {

        $interval = $interval ?? $this->interval;

        for ($i = 1; $i < $interval; $i++) {

            $from = new \DateTime();
            $to = new \DateTime();

            $from->sub(new \DateInterval('P' . $number * $i . 'D'));
            $to->sub(new \DateInterval('P' . $number * ($i - 1) . 'D'));

            try {

                $candles = array_reverse($this->client->getHistoryCandles($figi, $from, $to, $day));

            } catch (TIException $e) {

                $this->sendTelegramMessage('Can\'t get candles!!! FIGI is: ' . $figi);

                die();

            }

            if (empty($candles)) break;

            foreach ($candles as $candle) {

                $day_candles[] = [
                    'open' => $candle->getOpen(),
                    'close' => $candle->getClose(),
                    'high' => $candle->getHigh(),
                    'low' => $candle->getLow(),
                    'volume' => $candle->getVolume(),
                    'time_start' => Carbon::createFromTimestamp(
                        $candle->getTime()->getTimestamp(),
                        'Europe/Moscow'
                    )->toDateTimeString(),
                ];

            }

        }

        return $day_candles ?? [];

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

}
