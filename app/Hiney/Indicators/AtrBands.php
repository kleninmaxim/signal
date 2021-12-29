<?php

namespace App\Hiney\Indicators;

class AtrBands extends Indicator
{

    private int $atr_period;
    private int $atr_multiplier_upper;
    private int $atr_multiplier_lower;
    private mixed $source;

    public function __construct($atr_period = 14, $atr_multiplier_upper = 1, $atr_multiplier_lower = 1, $source = 'close')
    {

        $this->atr_period = $atr_period;
        $this->atr_multiplier_upper = $atr_multiplier_upper;
        $this->atr_multiplier_lower = $atr_multiplier_lower;
        $this->source = $source;

    }

    public function get($sources): array
    {

        $atrs = array_column((new Atr($this->atr_period))->get($sources), 'atr');

        if ($this->source != null)
            $sources = array_column($sources, $this->source);

        foreach ($sources as $key => $source) {

            $atr_bands[] = [
                'atr_band_upper' => empty($atrs[$key]) ? null : $source + $atrs[$key] * $this->atr_multiplier_upper,
                'atr_band_lower' => empty($atrs[$key]) ? null : $source - $atrs[$key] * $this->atr_multiplier_lower,
            ];

        }

        return $atr_bands ?? [];

    }

}
