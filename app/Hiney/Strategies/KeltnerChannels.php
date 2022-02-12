<?php

namespace App\Hiney\Strategies;

use App\Hiney\Indicators\KeltnerChannels as Keltner;

class KeltnerChannels
{

    private array $candles;

    public function __construct($candles, $length = 20, $multiplier = 1, $band_style = 'ATR', $atr_length = 10)
    {

        if (!in_array($band_style, ['ATR', 'R'])) debug('Band style not correct', true);

        $candles = array_values($candles);

        if (count($candles) >= 50) {

            (new Keltner($length, $multiplier, $band_style, $atr_length))->put($candles);

            $this->candles = array_reverse($candles);

        } else {

            // оповестить об ошибке

        }

    }

    public function test(): bool|array
    {

        return $this->candles;

    }

    public function run(): bool|array
    {

        return $this->candles;

    }

}
