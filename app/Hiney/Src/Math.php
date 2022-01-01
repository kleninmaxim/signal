<?php

namespace App\Hiney\Src;

class Math
{

    public static function round($number, $precision = 2): float
    {

        return round($number, $precision);

    }

    public static function percentage($x, $y): float
    {

        if ($y == 0) return 0;

        return round($x / $y * 100, 2);

    }

}
