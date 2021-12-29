<?php

namespace App\Hiney\Indicators;

class Sma extends Indicator
{

    private int $length;
    private mixed $source;

    public function __construct($length = 20, $source = 'close')
    {

        $this->length = $length;
        $this->source = $source;

    }

    public function get($sources): array
    {

        if ($this->source != null)
            $sources = array_column($sources, $this->source);

        foreach ($sources as $key => $source) {

            if ($key < $this->length - 1) {

                $sma[] = ['sma' => null];

                continue;

            }

            $sum = 0;

            for ($i = 0; $i < $this->length; $i++)
                $sum += $sources[$key - $i];

            $sma[] = ['sma' => $sum / $this->length];

        }

        return $sma ?? [];

    }

}
