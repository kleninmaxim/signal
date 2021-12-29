<?php

namespace App\Hiney;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class Binance
{

    public static function getCandles($pair, $timeframe, $limit = 100, $removeCurrent = false): array
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

        if ($removeCurrent == true) {

            $current_candle = array_pop($candles);

            if ($current_candle['time_start'] < self::maxCandleTimeStart($timeframe))
                $candles[] = $candles;

        }

        return array_values($candles ?? []);

    }

    private static function maxCandleTimeStart($timeframe): string
    {

        return date(
            'Y-m-d H:i:s',
            strtotime(date('Y-m-d H:i:s')) - self::timeframeInSeconds($timeframe)
        );

    }

    private static function timeframeInSeconds($timeframe): int
    {

        $timeframes = [
            '1m' => 60,
            '5m' => 5 * 60,
            '15m' => 15 * 60,
            '30m' => 30 * 60,
            '1h' => 60 * 60,
            '4h' => 4 * 60 * 60,
            '1d' => 24 * 60 * 60,
            '1w' => 7 * 24 * 60 * 60,
            '1M' => 30 * 24 * 60 * 60
        ];

        return $timeframes[$timeframe] ?? 0;

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
