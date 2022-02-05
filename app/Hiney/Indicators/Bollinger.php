<?php

namespace App\Hiney\Indicators;

class Bollinger extends Indicator
{

    private int $length;
    private int $multi;
    private mixed $source;

    public function __construct($length = 12, $multi = 1,  $source = 'close')
    {

        $this->length = $length;

        $this->multi = $multi;

        $this->source = $source;

    }

    public function get($sources): array
    {

        if ($this->source != null)
            $sources = array_column($sources, $this->source);

        $smas = array_column((new Sma($this->length, null))->get($sources), 'sma');

        $stdevs = array_column((new Stdev($this->length, null))->get($sources), 'stdev');

        foreach ($sources as $key => $source) {

            if ($key < $this->length - 1) {

                $stdev[] = ['bollinger_basic' => null, 'bollinger_upper' => null, 'bollinger_lower' => null];

                continue;

            }

            $stdev[] = [
                'bollinger_basic' => $smas[$key],
                'bollinger_upper' => $smas[$key] + $this->multi * $stdevs[$key],
                'bollinger_lower' => $smas[$key] - $this->multi * $stdevs[$key]
            ];

        }

        return $stdev ?? [];

    }

}
