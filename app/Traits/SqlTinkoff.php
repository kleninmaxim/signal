<?php

namespace App\Traits;

use App\Models\TinkoffDayCandle;
use App\Models\TinkoffHourCandle;
use App\Models\TinkoffMonthCandle;
use App\Models\TinkoffTicker;
use App\Models\TinkoffWeekCandle;
use Illuminate\Support\Facades\DB;

trait SqlTinkoff
{



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

    private function deleteTickerQueue($ticker)
    {
        DB::table('tinkoff_tikers_queue')->where('ticker', $ticker)->delete();
    }

}
