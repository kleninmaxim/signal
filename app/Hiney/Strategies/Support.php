<?php

namespace App\Hiney\Strategies;

use App\Hiney\Indicators\AtrBands;
use App\Hiney\Src\Math;
use App\Hiney\Src\Telegram;
use App\Models\ErrorLog;
use JetBrains\PhpStorm\ArrayShape;

class Support
{

    private array $candles;
    private string $side; // only SELL or BUY
    private string $buy = 'BUY';
    private string $sell = 'SELL';
    private float $change = 0.25; //necessary percentage change

    private float $loss = 5; // percentage risk of capital
    private array $position;

    public function __construct($candles, $side, $atr_parameter = 2)
    {

        $candles = array_values($candles);

        if (count($candles) >= 50) {

            (new AtrBands(atr_multiplier_upper: $atr_parameter, atr_multiplier_lower: $atr_parameter))->put($candles);

            $this->candles = array_reverse($candles);

            $this->side = $side;

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

        $current_candle = array_shift($this->candles);
        $last_formed_candle = array_shift($this->candles);

        if ($this->side == $this->buy) {

            if (
                Math::percentage(
                    ($current_candle['close'] - $last_formed_candle['atr_band_lower']),
                    $last_formed_candle['atr_band_lower']
                ) <= $this->change
            ) return false;

            return $this->position = [
                'position' => $this->side,
                'price' => $current_candle['close'],
                'stop_loss' => $last_formed_candle['atr_band_lower']
            ];

        } elseif ($this->side == $this->sell) {

            if (
                Math::percentage(
                    ($last_formed_candle['atr_band_upper'] - $current_candle['close']),
                    $last_formed_candle['atr_band_upper']
                ) <= $this->change
            ) return false;

            return $this->position = [
                'position' => $this->side,
                'price' => $current_candle['close'],
                'stop_loss' => $last_formed_candle['atr_band_upper']
            ];

        } else
            return false;

    }

    public function countAmount($total_margin_balance): float|int|bool
    {

        $loss = $total_margin_balance * $this->loss / 100;

        return isset($this->position)
            ? abs($loss / ($this->position['stop_loss'] - $this->position['price']))
            : false;

    }

    public function round(&$position, $precisions)
    {

        $position['price'] = Math::round($position['price'], $precisions['price_precision']);

        $position['stop_loss'] = Math::round($position['stop_loss'], $precisions['price_precision']);

        $position['amount'] = Math::round($position['amount'], $precisions['amount_precision']);

    }

}
