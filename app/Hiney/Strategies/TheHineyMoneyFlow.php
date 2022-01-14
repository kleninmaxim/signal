<?php

namespace App\Hiney\Strategies;

use App\Hiney\Indicators\AtrBands;
use App\Hiney\Indicators\HeikinAshi;
use App\Hiney\Indicators\Mfi;
use App\Hiney\Src\Math;
use App\Hiney\Src\Telegram;
use App\Models\ErrorLog;
use JetBrains\PhpStorm\Pure;

class TheHineyMoneyFlow
{

    private array $candles;
    private string $buy = 'BUY';
    private string $sell = 'SELL';

    private float $profit = 2;
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

            ErrorLog::create([
                'title' => 'Candles less than 50!',
                'message' => json_encode($candles),
            ]);

            (new Telegram())->send(
                'Candles less than 50!' . "\n"
            );

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

        }

        return false;

    }

    public function runReversal(): bool|array
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
                            'position' => ($current_position == $this->sell) ? $this->buy : $this->sell,
                            'price' => $current_candle['close'],
                            'take_profit' => $current_candle['atr_band_upper'],
                            'stop_loss' => $this->countTakeProfit($current_candle['close'], $current_candle['atr_band_upper']),
                        ];

                    } elseif (($candle['mfi'] <= 21 && $candle_position == $this->sell) || $current_long_position) {

                        return $this->position = [
                            'position' => ($current_position == $this->sell) ? $this->buy : $this->sell,
                            'price' => $current_candle['close'],
                            'take_profit' => $current_candle['atr_band_lower'],
                            'stop_loss' => $this->countTakeProfit($current_candle['close'], $current_candle['atr_band_lower']),
                        ];

                    }

                } else break;

            }

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

        $position['amount'] = Math::round($position['amount'] / $position['price'], $precisions['amount_precision']);

    }

    #[Pure] public function message($pair, $position, $timeframe): string
    {

        return
            'Strategy: The Hiney Money Flow' . "\n" .
            'Pair is: ' . $pair . "\n" .
            'Position is: ' . $position['position'] . "\n" .
            'Price is: ' . $position['price'] . "\n" .
            'Stop Loss is: ' . $position['stop_loss'] . "\n" .
            'Take Profit is: ' . $position['take_profit'] . "\n" .
            'Amount in USDT is: ' . Math::round($position['amount'] * $position['price']) . "\n" .
            'Amount: ' . $position['amount'] . "\n" .
            'Timeframe is: ' . $timeframe . "\n";

    }

    public function reversePosition($position): string
    {

        return ($position == $this->buy) ? $this->sell : $this->buy;

    }

    private function countTakeProfit($price, $stop_loss): float|int
    {

        return (5 * $price - $stop_loss) / 4;

    }

}
