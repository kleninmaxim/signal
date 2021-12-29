<?php

namespace App\Hiney;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class Binance
{

    public static function getCandles($pair, $timeframe, $limit = 100): array
    {

        foreach (self::getCandlesApi($pair, $timeframe, $limit) as $key => $candle)
            $candles[$key] = [
                'open' => $candle[1],
                'high' => $candle[2],
                'low' => $candle[3],
                'close' => $candle[4],
                'volume' => $candle[5],
                'time_start' => Carbon::createFromTimestamp($candle[0] / 1000)->toDateTimeString()
            ];

        return $candles ?? [];

    }

    private static function getCandlesApi($pair, $timeframe, $limit): array
    {

        return Http::get('https://api.binance.com/api/v3/klines', [
            'symbol' => $pair,
            'interval' => $timeframe,
            'limit' => $limit,
        ])->collect()->toArray();

    }

}
