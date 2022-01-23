<?php

namespace App\Http\Controllers;

use App\Hiney\BinanceFutures;
use App\Hiney\Strategies\AgressiveScalper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AgressiveScalperController extends Controller
{

    private string $disk = 'local';

    private string $file = 'precisions.json';

    public function agressiveScalperStrategy()
    {

        // таймфрейм
        $timeframe = '5m';

        // рынок
        $pair = 'BTCUSDT';

        // объект для взаимодействия с фьючерсами binance через API
        $binance_futures = new BinanceFutures();

        // создать экземпляр стратегии по свечам бинанса, включая текущую свечу
        $strategy = new AgressiveScalper(
            $binance_futures->getCandles($pair, $timeframe, 1000)
        );

        debug($strategy->run());

    }

}
