<?php

namespace App\Hiney\Indicators;

class Rma extends Indicator
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

                $rma[] = ['rma' => null];

                continue;

            }

            if (!isset($first)) {

                $sum = 0;

                for ($i = 0; $i < $this->length; $i++)
                    $sum += $sources[$key - $i];

                $rma[] = ['rma' => $sum / $this->length];

                $first = false;

            } else
                $rma[] = ['rma' => ($rma[$key - 1]['rma'] * ($this->length - 1) + $source) / $this->length];

        }

        return $rma ?? [];

    }

}
