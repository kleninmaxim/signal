<?php

namespace App\Hiney\Indicators;

class Stdev extends Indicator
{

    private int $length;
    private mixed $source;

    public function __construct($length = 12, $source = 'close')
    {

        $this->length = $length;

        $this->source = $source;

    }

    public function get($sources): array
    {

        if ($this->source != null)
            $sources = array_column($sources, $this->source);

        $smas = array_column((new Sma($this->length, null))->get($sources), 'sma');

        foreach ($sources as $key => $source) {

            if ($key < $this->length - 1) {

                $stdev[] = ['stdev' => null];

                continue;

            }

            $avg = $smas[$key];

            $sum_of_square_deviations = 0;

            for ($i = 0; $i < $this->length; $i++)
                $sum_of_square_deviations += pow($this->sum($sources[$key - $i], -$avg), 2);

            $stdev[] = ['stdev' => sqrt($sum_of_square_deviations / $this->length)];

        }

        return $stdev ?? [];

    }

    private function sum($fst, $snd)
    {

        $res = $fst + $snd;

        if ($this->isZero($res, 0.0000000001))
            return 0;

        if (!$this->isZero($res, 0.0001))
            return $res;

        return 15;

    }


    private function isZero($val, $eps): bool
    {

        return abs($val) <= $eps;

    }

}
