<?php

namespace App\Hiney;

use App\Hiney\Src\Telegram;
use WebSocket\Client;
use WebSocket\ConnectionException;

class BinanceFuturesSocket
{

    private static mixed $client;
    private static string $pair;

    public static function connect($pair)
    {

        self::$client = new Client(
            'wss://fstream.binance.com/ws/' . mb_strtolower($pair) . '@miniTicker',
            ['timeout' => 100]
        );

        self::$pair = $pair;

    }

    public static function run(): array|bool
    {

        if (is_null(self::$client)) return false;
        if (is_null(self::$pair)) return false;

        try {

            $kline = json_decode(self::$client->receive(), true);

            return [
                'open' => $kline['o'],
                'close' => $kline['c'],
                'high' => $kline['h'],
                'low' => $kline['l'],
                'volume' => $kline['v'],
                'event_time' => $kline['E']
            ];

            return true;

        } catch (ConnectionException $e) {

            (new Telegram(false))->send('Can\'t get data by websocket' . "\n");

            return false;

        }

    }

    public static function close()
    {

        if (!is_null(self::$client)) self::$client->close();

    }

}
