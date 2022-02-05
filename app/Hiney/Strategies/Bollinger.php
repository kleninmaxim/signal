<?php

namespace App\Hiney\Strategies;

use App\Hiney\Indicators\Bollinger as Bol;

class Bollinger
{

    private array $candles;

    public function __construct($candles, $length = 20, $multi = 1)
    {

        $candles = array_values($candles);

        if (count($candles) >= 50) {

            (new Bol($length, $multi))->put($candles);

            $this->candles = array_reverse($candles);

        } else {

            // оповестить об ошибке

        }

    }

    public function run(): bool|array
    {

        return $this->candles;

    }

}
