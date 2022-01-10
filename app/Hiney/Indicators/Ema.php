<?php

namespace App\Hiney\Indicators;

class Ema extends Indicator
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

                $ema[] = ['ema' => null];

            } elseif ($key == $this->length - 1) {

                $sum = 0;

                for ($i = 0; $i < $this->length; $i++)
                    $sum += $sources[$key - $i];

                $ema[] = ['ema' => $sum / $this->length];

            } else
                $ema[] = ['ema' => 2 / ($this->length + 1) * $source + (1 - 2 / ($this->length + 1)) * $ema[$key - 1]['ema']];

        }

        return $ema ?? [];

    }

}
