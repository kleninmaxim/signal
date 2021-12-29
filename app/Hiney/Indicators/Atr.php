<?php

namespace App\Hiney\Indicators;

class Atr extends Indicator
{

    private int $atr_period;

    public function __construct($atr_period = 14)
    {

        $this->atr_period = $atr_period;

    }

    public function get($sources): array
    {

        $trueRanges = [];

        foreach ($sources as $key => $source) {

            $trueRanges[$key] = !isset($sources[$key - 1]['high'])
                ? $source['high'] - $source['low']
                : max(
                    $source['high'] - $source['low'],
                    abs($source['high'] - $sources[$key - 1]['close']),
                    abs($source['low'] - $sources[$key - 1]['close'])
                );

        }

        foreach ((new Rma($this->atr_period, null))->get($trueRanges) as $key => $rma)
            $atrs[$key]['atr'] = $rma['rma'];

        return $atrs ?? [];

    }
}
