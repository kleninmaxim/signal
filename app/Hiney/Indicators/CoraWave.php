<?php

namespace App\Hiney\Indicators;

class CoraWave extends Indicator
{

    private int $length;
    private int $r_multi;
    private bool $smooth;
    private int $man_smooth;

    public function __construct($length = 20, $r_multi = 2, $smooth = true, $man_smooth = 1)
    {

        $this->length = $length;
        $this->r_multi = $r_multi;
        $this->smooth = $smooth;
        $this->man_smooth = $man_smooth;

    }

    public function get($sources): array
    {

        $candles = array_reverse($sources);

        foreach ($candles as $key => $candle)
            $candles[$key]['hlc3'] = ($candle['high'] + $candle['low'] + $candle['close']) / 3;

        return $this->wma(
            array_reverse($this->f_adj_crwma($candles ?? [])),
            $this->smooth ? max(round(sqrt($this->length)), 1) : $this->man_smooth
        );

    }

    private function wma($candles, $length): array
    {
        $candles = array_reverse($candles);

        $all = count($candles);

        foreach ($candles as $key => $candle) {

            if ($key + $length > $all) {
                $signals[] = ['cora_wave' => 0];
                continue;
            }

            $norm = 0;

            $sum = 0;

            for ($i = 0; $i <= $length - 1; $i++) {

                $weight = ($length - $i) * $length;

                $norm += $weight;

                $sum += $candles[$key + $i] * $weight;

            }

            $signals[] = ['cora_wave' => $sum / $norm];

        }

        return array_reverse($signals ?? []);

    }

    private function f_adj_crwma($sources): array
    {
        $sources = array_values($sources);

        $all = count($sources);

        foreach ($sources as $key => $source) {

            if ($key + $this->length > $all) {
                $signals[] = 0;
                continue;
            }

            $numerator = 0;

            $denom = 0;

            $c_weight = 0;

            $End_Wt = $this->length;

            $r = pow(($End_Wt / 0.01), (1 / ($this->length - 1))) - 1;

            $base = 1 + $r * $this->r_multi;

            for ($i = 0; $i < $this->length - 1; $i++) {

                $c_weight = 0.01 * pow($base, ($this->length - $i));

                $numerator += $sources[$key + $i]['hlc3'] * $c_weight;

                $denom += $c_weight;

            }

            $signals[] = $numerator / $denom;

        }


        return $signals ?? [];

    }

}
