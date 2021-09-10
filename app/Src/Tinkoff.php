<?php

namespace App\Src;

use App\Models\TinkoffDayCandle;
use App\Models\TinkoffFourHourCandle;
use App\Models\TinkoffHourCandle;
use App\Models\TinkoffTicker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TIException;
use jamesRUS52\TinkoffInvest\TIIntervalEnum;
use jamesRUS52\TinkoffInvest\TISiteEnum;
use App\Src\Telegram;
use App\Src\StrategyTest;

class Tinkoff
{

    private $client;
    private $interval = 120;

    public $tinkoff_telegram_token;
    public $tinkoff_chat_id;

    public function __construct()
    {

        $this->client = new TIClient(config('api.tinkoff_token'), TISiteEnum::EXCHANGE);

        $this->tinkoff_telegram_token = config('api.telegram_token_2');
        $this->tinkoff_chat_id = config('api.chat_id_2');

    }

    public function test()
    {

        $stategy_test = new StrategyTest();

        $stategy_test->testTinkoff();

    }

    public function addNewTicker($ticker)
    {
        $ticker_data = TinkoffTicker::where('ticker', $ticker)->first();

        if (empty($ticker_data)) {

            DB::table('tinkoff_tikers_queue')->insertOrIgnore([
                'ticker' => $ticker
            ]);

        }

        return $ticker;
    }

    public function loadCandles()
    {

        $ticker = DB::table('tinkoff_tikers_queue')->select('ticker')->first();

        if (!empty($ticker)) {

            try {

                $ticker_data = $this->client->getInstrumentByTicker($ticker->ticker);

            } catch (TIException $e) {

                $this->deleteTickerQueue($ticker->ticker);

                debug('No such ticker');

                die();

            }

            $hour_candles = $this->getHourCandles($ticker_data->getFigi());

            $hours = $this->getHours($hour_candles);

            $this->formatCandles($hour_candles);

            $four_hour_candles = $this->getFourHoursCandles($hour_candles, $hours);

            $day_candles = $this->getDayCandlesAPI($ticker_data->getFigi());

            if ($this->checkCurrentTimeWithCandle($hour_candles)) array_pop($hour_candles);

            $id = $this->insertTicker($ticker_data);
            $this->deleteTickerQueue($ticker->ticker);
            $this->insertHourCandle($hour_candles, $id);
            $this->insertFourHourCandle($four_hour_candles, $id);
            $this->insertDayCandle($day_candles, $id);

            return true;

        }

        return false;

    }

    public function updateDayCandles()
    {

        $ticker = DB::table('tinkoff_tikers_queue')->select('ticker')->first();

        if (!empty($ticker)) {

            try {

                $ticker_data = $this->client->getInstrumentByTicker($ticker->ticker);

            } catch (TIException $e) {

                $this->deleteTickerQueue($ticker->ticker);

                debug('No such ticker');

                die();

            }

            $day_candles = $this->getDayCandlesAPI($ticker_data->getFigi());

            if ($this->checkCurrentTimeWithCandle($day_candles)) array_pop($day_candles);

            $id = TinkoffTicker::where('figi', $ticker_data->getFigi())->first()->toArray();

            $this->deleteTickerQueue($ticker->ticker);

            $this->insertDayCandle($day_candles, $id['id']);

            return true;

        }

        return false;

    }

    public function updateCandles($ticker)
    {

        $ticker = TinkoffTicker::where('ticker', $ticker)->first();

        if (!empty($ticker)) {

            $last_time = TinkoffHourCandle::where('tinkoff_ticker_id', $ticker->id)
                ->orderBy('time_start', 'desc')->first()->time_start;

            $hour_candles = $this->getHourCandles($ticker->figi, 2);

            $hours = $this->getHours($hour_candles);

            $this->formatUpdateCandles($hour_candles, $last_time);

            if (!empty($hour_candles)) {

                $hour_candles = array_reverse($hour_candles);

                if ($this->checkCurrentTimeWithCandle($hour_candles)) $last_candle = array_pop($hour_candles);

                $this->insertHourCandle($hour_candles, $ticker->id);

                if (isset($last_candle)) $hour_candles[] = $last_candle;

                $this->updateFourHoursCandles($ticker->id, $hours);

                $this->updateFourDayCandles($ticker->id, $hours);

            }

        }

        return $last_candle ?? null;

    }

    public function getAllTickers()
    {
        return TinkoffTicker::where('notify', true)->get();
    }

    public function getCandles($ticker, $timeframe)
    {

        $last_candle = $this->updateCandles($ticker->ticker);

        if ($timeframe == '1h') {

            $candles = TinkoffHourCandle::where('tinkoff_ticker_id', $ticker->id)
                ->orderBy('time_start', 'desc')
                ->limit(800)
                ->get()
                ->reverse()
                ->toArray();

        } elseif ($timeframe == '4h') {

            $candles = TinkoffFourHourCandle::where('tinkoff_ticker_id', $ticker->id)
                ->orderBy('time_start', 'desc')
                ->limit(800)
                ->get()
                ->reverse()
                ->toArray();

        } elseif ($timeframe == '1d') {

            $candles = TinkoffDayCandle::where('tinkoff_ticker_id', $ticker->id)
                ->orderBy('time_start', 'desc')
                ->limit(800)
                ->get()
                ->reverse()
                ->toArray();

        }

        if (isset($candles)) {

            if ($last_candle != null) $candles[] = $last_candle;

            return $candles;

        }

        return [];
    }

    private function updateFourHoursCandles($id, $hours)
    {

        $hours_candles_for_four_hour_candles = $this->getHourCandlesMoreThenFourHourCandles($id);

        if (!empty($hours_candles_for_four_hour_candles)) {

            $four_candles = $this->getFourHoursCandles($hours_candles_for_four_hour_candles, $hours);

            $this->insertFourHourCandle($four_candles, $id);

        }

    }

    private function updateFourDayCandles($id, $hours)
    {

        $hours_candles_for_day_candles = $this->getHourCandlesMoreThenDayCandles($id);

        if (!empty($hours_candles_for_day_candles)) {

            $day_candles = $this->getDayCandles($hours_candles_for_day_candles, $hours);

            $this->insertDayCandle($day_candles, $id);

        }

    }

    private function getHourCandlesMoreThenFourHourCandles($id)
    {

        return TinkoffHourCandle::where([
            ['tinkoff_ticker_id', $id],
            ['time_start',
                '>=',
                Carbon::parse(
                    TinkoffFourHourCandle::where('tinkoff_ticker_id', $id)
                        ->orderBy('time_start', 'desc')
                        ->first()
                        ->time_start
                )->addHours(4)
            ],
        ])->get()->toArray();

    }

    private function getHourCandlesMoreThenDayCandles($id)
    {

        return TinkoffHourCandle::where([
            ['tinkoff_ticker_id', $id],
            ['time_start',
                '>=',
                Carbon::parse(
                    TinkoffDayCandle::where('tinkoff_ticker_id', $id)
                        ->orderBy('time_start', 'desc')
                        ->first()
                        ->time_start
                )->addDay()
            ],
        ])->get()->toArray();

    }

    private function formatUpdateCandles(&$hour_candles, $last_time)
    {

        foreach ($hour_candles as $candle) {

            $candle_pre['open'] = $candle['open'];
            $candle_pre['close'] = $candle['close'];
            $candle_pre['high'] = $candle['high'];
            $candle_pre['low'] = $candle['low'];
            $candle_pre['volume'] = $candle['volume'];
            $candle_pre['time_start'] = $candle['time_start'];

            if ($last_time == $candle_pre['time_start']) break;

            $candles[] = $candle_pre;

        }

        $hour_candles = $candles ?? [];

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

                debug('Can\'t get candles!!!');

                $telegram = new Telegram(
                    $this->tinkoff_telegram_token,
                    $this->tinkoff_chat_id
                );

                $telegram->send('Can\'t get candles!!!');

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

    private function getDayCandlesAPI($figi, $interval = null)
    {

        $interval = $interval ?? $this->interval;

        for ($i = 1; $i < $interval; $i++) {

            $from = new \DateTime();
            $to = new \DateTime();

            $from->sub(new \DateInterval('P' . 365 * $i . 'D'));
            $to->sub(new \DateInterval('P' . 365 * ($i - 1) . 'D'));

            try {

                $candles = array_reverse($this->client->getHistoryCandles($figi, $from, $to, TIIntervalEnum::DAY));

            } catch (TIException $e) {

                debug('Can\'t get candles!!!');

                $telegram = new Telegram(
                    $this->tinkoff_telegram_token,
                    $this->tinkoff_chat_id
                );

                $telegram->send('Can\'t get candles!!!');

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

    private function insertFourHourCandle($four_hour_candles, $id)
    {
        foreach ($four_hour_candles as $four_hour_candle) {
            TinkoffFourHourCandle::create([
                'tinkoff_ticker_id' => $id,
                'open' => $four_hour_candle['open'],
                'close' => $four_hour_candle['close'],
                'high' => $four_hour_candle['high'],
                'low' => $four_hour_candle['low'],
                'volume' => $four_hour_candle['volume'],
                'time_start' => $four_hour_candle['time_start']
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

    private function getFourHoursCandles($hour_candles, $hours)
    {
        $four_candles_pre = [];

        foreach ($hour_candles as $hour_candle) {

            $hour = Carbon::parse($hour_candle['time_start'])->hour;

            if (in_array($hour, $hours) && !empty($four_candles_pre)) {

                $this->formCandlesPre($four_candles_pre);

                $four_candles[] = $four_candles_pre;

                $four_candles_pre = [];

            }

            $this->addItemCandlesPre($four_candles_pre, $hour_candle);


        }

        return $four_candles ?? [];

    }

    private function getDayCandles($hour_candles, $hours)
    {

        $day_candles_pre = [];

        foreach ($hour_candles as $hour_candle) {

            $hour = Carbon::parse($hour_candle['time_start'])->hour;

            if ($hour == $hours[0] && !empty($day_candles_pre)) {


                $this->formCandlesPre($day_candles_pre);

                $day_candles[] = $day_candles_pre;

                $day_candles_pre = [];

            }

            $this->addItemCandlesPre($day_candles_pre, $hour_candle);

        }

        return $day_candles ?? [];

    }

    private function addItemCandlesPre(&$candles, $hour_candle)
    {

        $candles['open'][] = $hour_candle['open'];
        $candles['close'][] = $hour_candle['close'];
        $candles['high'][] = $hour_candle['high'];
        $candles['low'][] = $hour_candle['low'];
        $candles['volume'][] = $hour_candle['volume'];
        $candles['time_start'][] = $hour_candle['time_start'];

    }

    private function formCandlesPre(&$candles)
    {

        $candles['open'] = array_shift($candles['open']);
        $candles['close'] = array_pop($candles['close']);
        $candles['high'] = max($candles['high']);
        $candles['low'] = min($candles['low']);
        $candles['volume'] = array_sum($candles['volume']);
        $candles['time_start'] = array_shift($candles['time_start']);

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

    private function getHours($candles)
    {
        foreach ($candles as $candle) {

            $hours[] = Carbon::parse($candle['time_start'])->hour;

        }

        $hours = array_unique($hours);

        sort($hours);

        foreach ($hours as $key => $hour) {

            if ($hour <= 5) {

                $after[] = $hour;

                unset($hours[$key]);

            }

        }

        $hours = isset($after) ? array_merge($hours, $after) : $hours;

        $i = 0;

        foreach ($hours as $key => $hour) {

            if ($i % 4 != 0) unset($hours[$key]);

            $i++;

        }

        return $hours;
    }

    private function deleteTickerQueue($ticker)
    {
        DB::table('tinkoff_tikers_queue')->where('ticker', $ticker)->delete();
    }

}
