<?php

namespace App\Hiney\Indicators;

class KeltnerChannels extends Indicator
{

    private int $length;
    private mixed $multiplier;
    private mixed $band_style;
    private mixed $atr_length;
    private mixed $source;

    public function __construct($length = 20, $multiplier = 1, $band_style = 'ATR', $atr_length = 10, $source = 'close')
    {

        $this->length = $length;

        $this->multiplier = $multiplier;

        $this->band_style = $band_style;

        $this->atr_length = $atr_length;

        $this->source = $source;

    }

    public function get($sources): array
    {

        $mas = array_column((new Sma($this->length, $this->source))->get($sources), 'sma');

        if ($this->band_style == 'ATR') {

            $rangema = array_column((new Atr($this->atr_length))->get($sources), 'atr');

        } elseif ($this->band_style == 'R') {

            $rma_sources = array_map(
                fn($source) => $source['high'] - $source['low'],
                $sources
            );

            $rangema = array_column((new Rma($this->length, null))->get($rma_sources), 'rma');

        }

        foreach ($mas as $key => $ma) {

            if ($key < $this->length - 1) {

                $keltner_channels[] = [
                    'keltner_channel_basic' => null,
                    'keltner_channel_upper' => null,
                    'keltner_channel_lower' => null
                ];

                continue;

            }

            $keltner_channels[] = [
                'keltner_channel_basic' => $ma,
                'keltner_channel_upper' => $ma + $this->multiplier * $rangema[$key],
                'keltner_channel_lower' => $ma - $this->multiplier * $rangema[$key]
            ];

        }

        return $keltner_channels ?? [];

    }

}
