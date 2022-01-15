<?php

namespace App\Hiney\Indicators;

class RSI extends Indicator
{

    private int $length;
    private string $source;
    private string $name;

    public function __construct($length = 14, $source = 'close', $name = 'rsi')
    {

        $this->length = $length;
        $this->source = $source;
        $this->name = $name;

    }

    public function get($sources): array
    {

        $ups = [];
        $downs = [];

        $up = [];
        $down = [];

        if ($this->source != null)
            $sources = array_column($sources, $this->source);

        foreach ($sources as $key => $source) {

            $change_close = !isset($sources[$key - 1]) ? 0 : $source - $sources[$key - 1];

            $ups[$key] = max($change_close, 0);

            $downs[$key] = -1 * min($change_close, 0);

        }

        foreach ((new Rma($this->length, null))->get($ups) as $key => $rma)
            $up[$key] = $rma['rma'];

        foreach ((new Rma($this->length, null))->get($downs) as $key => $rma)
            $down[$key] = $rma['rma'];

        foreach ($up as $key => $item)
            $rsi[] = [
                $this->name =>
                    ($down[$key] == 0)
                        ? 100
                        : (
                    ($item == 0)
                        ? 0
                        : 100 - (100 / (1 + $item / $down[$key]))
                    )
            ];

        return $rsi ?? [];

    }

}
