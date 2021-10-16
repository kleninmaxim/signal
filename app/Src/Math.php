<?php

namespace App\Src;

use Carbon\Carbon;

class Math
{

    public static function minuteToDays($minutes)
    {

        return round($minutes / (60 * 24));

    }

    public static function percentage($x, $y)
    {

        return round(($y - $x) / $x * 100, 2);

    }

    public static function annualApy($percentage, $minutes)
    {

        return ($minutes != 0)
            ? round($percentage * 525600 / $minutes, 2)
            : 0;

    }

    public static function diffInMinutes($time_start, $time_end)
    {

        return Carbon::parse($time_end)->diffInMinutes(Carbon::parse($time_start));

    }

}
