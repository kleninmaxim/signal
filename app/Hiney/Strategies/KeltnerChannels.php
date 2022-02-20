<?php

namespace App\Hiney\Strategies;

use App\Hiney\Indicators\KeltnerChannels as Keltner;
use App\Hiney\Src\Telegram;

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
            (new Telegram())->send(
                'Candles less than 50!' . "\n"
            );

        }

    }

    public function test(): bool|array
    {

        if (isset($this->candles))
            return $this->candles;

        return false;

    }

    public function run(): bool|array
    {

        if (isset($this->candles)) {

            $current_candle = array_shift($this->candles);;

            if ($current_candle['close'] <= $current_candle['keltner_channel_lower']) {

                return [
                    'position' => 'sell',
                    'price' => $current_candle['high']
                ];

            } elseif ($current_candle['close'] >= $current_candle['keltner_channel_upper']) {

                return [
                    'position' => 'buy',
                    'price' => $current_candle['high']
                ];

            }

        }

        return false;

    }

}
