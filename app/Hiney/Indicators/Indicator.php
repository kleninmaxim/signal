<?php

namespace App\Hiney\Indicators;

abstract class Indicator
{

    abstract public function get($sources) : array;

    public function put(&$candles)
    {

        $candles = array_replace_recursive($candles, $this->get($candles));

    }

}
