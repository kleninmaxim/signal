<?php

namespace App\Hiney\Indicators;

class HeikinAshi extends Indicator
{

    public function get($sources) : array
    {

        foreach ($sources as $key => $source) {

            if (!isset($sources[$key - 1])) {

                $heikin_ashi[] = [
                    'haClose' => null,
                    'haOpen' => null,
                    'haHigh' => null,
                    'haLow' => null,
                ];

                continue;

            }

            $haClose = ($source['close'] + $source['open'] + $source['high'] + $source['low']) / 4;

            $haOpen = isset($heikin_ashi[$key - 1]['haOpen'])
                ? ($heikin_ashi[$key - 1]['haOpen'] + $heikin_ashi[$key - 1]['haClose']) / 2
                : ($sources[$key - 1]['open'] + $sources[$key - 1]['close']) / 2;

            $heikin_ashi[] = [
                'haClose' => $haClose,
                'haOpen' => $haOpen,
                'haHigh' => max($source['high'], $haClose, $haOpen),
                'haLow' => min($source['low'], $haClose, $haOpen),
            ];

        }

        return $heikin_ashi ?? [];

    }

}
