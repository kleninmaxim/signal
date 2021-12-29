<?php

namespace App\Hiney\Indicators;

class Mfi extends Indicator
{

    private int $length;

    public function __construct($length = 14)
    {

        $this->length = $length;

    }

    public function get($sources) : array
    {

        $hlc3 = [];
        $money_flows = [];

        foreach ($sources as $source)
            $hlc3[] = ($source['high'] + $source['low'] + $source['close']) / 3;

        foreach ($hlc3 as $key => $item)
            if (isset($hlc3[$key - 1]) && $item < $hlc3[$key - 1])
                $money_flows[] = -1 * $item * $sources[$key]['volume'];
            else
                $money_flows[] = $item * $sources[$key]['volume'];

        foreach ($money_flows as $key => $money_flow) {

            if ($key < $this->length - 1) {

                $mfi[] = ['mfi' => null];

                continue;

            }

            $negative = 0;
            $positive = 0;

            for ($i = 0; $i < $this->length; $i++)
                if ($money_flows[$key - $i] < 0)
                    $negative += $money_flows[$key - $i];
                else
                    $positive += $money_flows[$key - $i];

            $mfi[] = ['mfi' => ($negative == 0) ? 100 : 100 - (100 / (1 - $positive / $negative))];

        }

        return $mfi ?? [];

    }

}
