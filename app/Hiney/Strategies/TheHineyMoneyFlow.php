<?php

namespace App\Hiney\Strategies;

use App\Hiney\Indicators\AtrBands;
use App\Hiney\Indicators\HeikinAshi;
use App\Hiney\Indicators\Mfi;
use App\Hiney\Src\Math;

class TheHineyMoneyFlow
{

    private array $candles;
    private string $buy = 'BUY';
    private string $sell = 'SELL';

    private float $profit = 1;
    private array $position;

    public function __construct($candles)
    {

        $candles = array_values($candles);

        if (count($candles) >= 50) {

            (new HeikinAshi())->put($candles);

            (new Mfi())->put($candles);

            (new AtrBands(atr_multiplier_upper:  5, atr_multiplier_lower: 5))->put($candles);

            $this->candles = array_slice(array_reverse($candles), 0, 10);

        } else {

            /* TODO: отправить сообщение об ошибке */

        }

    }

    public function run(): bool|array
    {

        if (isset($this->candles)) {

            $current_candle = array_shift($this->candles);

            $current_position = ($current_candle['haOpen'] > $current_candle['haClose']) ? $this->sell : $this->buy;

            $current_short_position = $current_candle['mfi'] >= 79 && $current_position == $this->sell;

            $current_long_position = $current_candle['mfi'] <= 21 && $current_position == $this->buy;

            foreach ($this->candles as $candle) {

                $candle_position = ($candle['haOpen'] > $candle['haClose']) ? $this->sell : $this->buy;

                if ($candle_position != $current_position) {

                    if (($candle['mfi'] >= 79 && $candle_position == $this->buy) || $current_short_position) {

                        return $this->position = [
                            'position' => $current_position,
                            'price' => $current_candle['close'],
                            'stop_loss' => $current_candle['atr_band_upper'],
                            'take_profit' => $this->countTakeProfit($current_candle['close'], $current_candle['atr_band_upper']),
                        ];

                    } elseif (($candle['mfi'] <= 21 && $candle_position == $this->sell) || $current_long_position) {

                        return $this->position = [
                            'position' => $current_position,
                            'price' => $current_candle['close'],
                            'stop_loss' => $current_candle['atr_band_lower'],
                            'take_profit' => $this->countTakeProfit($current_candle['close'], $current_candle['atr_band_lower']),
                        ];

                    }

                } else break;

            }

        } else {

            /* TODO: отправить сообщение об ошибке */

        }

        return false;

    }

    public function countAmount(): float|int|bool
    {

        return isset($this->position)
            ? abs($this->profit * $this->position['price'] / ($this->position['take_profit'] - $this->position['price']))
            : false;

    }

    public function round(&$position, $precisions)
    {

        $position['price'] = Math::round($position['price'], $precisions['price_precision']);

        $position['stop_loss'] = Math::round($position['stop_loss'], $precisions['price_precision']);

        $position['take_profit'] = Math::round($position['take_profit'], $precisions['price_precision']);

        $position['amount'] = Math::round($position['amount'], $precisions['amount_precision']);

    }

    public function message($pair, $position, $timeframe): string
    {

        return
            'Strategy: The Hiney Money Flow' . "\n" .
            'Pair is: ' . $pair . "\n" .
            'Position is: ' . $position['position'] . "\n" .
            'Price is: ' . $position['price'] . "\n" .
            'Stop Loss is: ' . $position['stop_loss'] . "\n" .
            'Take Profit is: ' . $position['take_profit'] . "\n" .
            'Amount is: ' . $position['amount'] . "\n" .
            'Timeframe is: ' . $timeframe . "\n";

    }

    private function countTakeProfit($price, $stop_loss): float|int
    {

        return (5 * $price - $stop_loss) / 4;

    }

}
