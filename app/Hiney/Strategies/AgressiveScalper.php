<?php

namespace App\Hiney\Strategies;

use App\Hiney\Indicators\Atr;
use App\Hiney\Indicators\RSI;
use App\Hiney\Src\Telegram;
use App\Models\ErrorLog;

class AgressiveScalper
{

    public function __construct($candles)
    {

        $candles = array_values($candles);

        if (count($candles) >= 50) {

            (new RSI())->put($candles);
            (new Atr(20, 'atr_fast'))->put($candles);
            (new Atr(100, 'atr_slow'))->put($candles);

            $this->candles = array_reverse($candles);

        } else {

            ErrorLog::create([
                'title' => 'Candles less than 50!',
                'message' => json_encode($candles),
            ]);

            (new Telegram(false))->send(
                'Candles less than 50!' . "\n"
            );

        }

    }

    public function run(): bool|array
    {

        return $this->candles;

    }

}
