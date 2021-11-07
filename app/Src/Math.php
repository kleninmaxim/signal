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

    public static function change($x, $y)
    {

        return round($y / $x, 6);

    }

    public static function round($number, $precision = 2)
    {

        return round($number, $precision);

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

    public static function statisticAnalyse($sampling)
    {

        //1 этап расчет всех основных коэффициентов

        $n = count($sampling);

        if ($n <= 3) return false;

        $R = max($sampling) - min($sampling);

        $K = round(pow($n, 1 / 2));

        if ($K < 5) $K = 5;
        if ($K > 20) $K = 20;

        $d = $R / $K;

        $table = self::createStatisticTable($sampling, $K, $d, $n);

        $x_average = self::statisticAverage($table);

        $sample_variance = self::statisticSampleVariance($table, $x_average, $n);

        $standard_deviation = pow($sample_variance, 1 / 2);

        $coefficient_of_variation = ($x_average != 0) ? $standard_deviation / $x_average : 0;

        $the_central_moment_three = self::statisticCentralMoment($table, $x_average, $n, 3);

        $the_central_moment_two = self::statisticCentralMoment($table, $x_average, $n, 2);

        $the_central_moment_four = self::statisticCentralMoment($table, $x_average, $n, 4);

        $the_coefficient_of_asymmetry = $the_central_moment_three / pow($the_central_moment_two, 3 / 2);

        $estimation_of_the_excess = $the_central_moment_four / pow($the_central_moment_two, 2) - 3;

        //2 этап Предварительная проверка на нормальность (68, 95, 100)

        $first_interval = self::statisticPercantageInSampling($sampling, $n, $x_average, $standard_deviation);

        $second_interval = self::statisticPercantageInSampling($sampling, $n, $x_average, 2 * $standard_deviation);

        $third_interval = self::statisticPercantageInSampling($sampling, $n, $x_average, 3 * $standard_deviation);

        //3 этап Интервальное оценивание

        $student = [
            '0.1' => [
                1 => 6.3138,
                2 => 2.9200,
                3 => 2.3534,
                4 => 2.1318,
                5 => 2.0150,
                6 => 1.9432,
                7 => 1.8946,
                8 => 1.8595,
                9 => 1.8331,
                10 => 1.8125,
                11 => 1.7959,
                12 => 1.7823,
                13 => 1.7709,
                14 => 1.7613,
                15 => 1.7531,
                16 => 1.7459,
                17 => 1.7396,
                18 => 1.7341,
                19 => 1.7291,
                20 => 1.7247,
                21 => 1.7207,
                22 => 1.7171,
                23 => 1.7139,
                24 => 1.7109,
                25 => 1.7081,
                26 => 1.7056,
                27 => 1.7033,
                28 => 1.7011,
                29 => 1.6991,
                30 => 1.6973,
                35 => 1.6896,
                40 => 1.6839,
                45 => 1.6794,
                50 => 1.6759,
                60 => 1.6706,
                70 => 1.6669,
                80 => 1.6641,
                90 => 1.6620,
                100 => 1.6602,
                120 => 1.6577,
                200 => 1.6525,
                1000 => 1.6449,
            ],
            '0.05' => [
                1 => 12.7062,
                2 => 4.3027,
                3 => 3.1824,
                4 => 2.7764,
                5 => 2.5706,
                6 => 2.4469,
                7 => 2.3646,
                8 => 2.3060,
                9 => 2.2622,
                10 => 2.2281,
                11 => 2.2010,
                12 => 2.1788,
                13 => 2.1604,
                14 => 2.1448,
                15 => 2.1314,
                16 => 2.1199,
                17 => 2.1098,
                18 => 2.1009,
                19 => 2.0930,
                20 => 2.0860,
                21 => 2.0796,
                22 => 2.0739,
                23 => 2.0687,
                24 => 2.0639,
                25 => 2.0595,
                26 => 2.0555,
                27 => 2.0518,
                28 => 2.0484,
                29 => 2.0452,
                30 => 2.0423,
                35 => 2.0301,
                40 => 2.0211,
                45 => 2.0141,
                50 => 2.0086,
                60 => 2.0003,
                70 => 1.9944,
                80 => 1.9901,
                90 => 1.9867,
                100 => 1.9840,
                120 => 1.9799,
                200 => 1.9719,
                1000 => 1.9600,
            ],
            '0.01' => [
                1 => 63.6567,
                2 => 9.9248,
                3 => 5.8409,
                4 => 4.6041,
                5 => 4.0321,
                6 => 3.7074,
                7 => 3.4995,
                8 => 3.3554,
                9 => 3.2498,
                10 => 3.1693,
                11 => 3.1058,
                12 => 3.0545,
                13 => 3.0123,
                14 => 2.9768,
                15 => 2.9467,
                16 => 2.9208,
                17 => 2.8982,
                18 => 2.8784,
                19 => 2.8609,
                20 => 2.8453,
                21 => 2.8314,
                22 => 2.8188,
                23 => 2.8073,
                24 => 2.7969,
                25 => 2.7874,
                26 => 2.7787,
                27 => 2.7707,
                28 => 2.7633,
                29 => 2.7564,
                30 => 2.7500,
                35 => 2.7238,
                40 => 2.7045,
                45 => 2.6896,
                50 => 2.6778,
                60 => 2.6603,
                70 => 2.6479,
                80 => 2.6387,
                90 => 2.6316,
                100 => 2.6259,
                120 => 2.6174,
                200 => 2.6006,
                1000 => 2.5758,
            ],
        ];

        $alpha = array_keys($student);

        foreach ($alpha as $a) {

            $n_arr = array_keys($student[$a]);

            $near = array_reduce($n_arr, function ($carry, $item) use ($n) {
                return $item <= $n? max($carry, $item): $carry;
            });

            $delta[$a] = self::round($student[$a][$near] * $standard_deviation / pow($n, 1 / 2));

        }

        return [
            'table' => $table,
            'average' => self::round($x_average),
            'standard_deviation' => self::round($standard_deviation),
            //'coefficient_of_variation' => self::round($coefficient_of_variation),
            //'first_interval' => self::round($first_interval),
            //'second_interval' => self::round($second_interval),
            //'third_interval' => self::round($third_interval),
            'delta' => $delta['0.01'] ?? [],
        ];

    }

    private static function statisticPercantageInSampling($sampling, $n, $x_average, $standard_deviation)
    {

        $i = 0;

        foreach ($sampling as $item) {

            if ($item >= $x_average - $standard_deviation && $item <= $x_average + $standard_deviation) $i++;

        }

        return $i / $n * 100;

    }

    private static function statisticCentralMoment($table, $x_average, $n, $l)
    {

        $the_central_moment = 0;

        foreach ($table as $t) {

            $the_central_moment += $t['m'] * pow(($t['u'] - $x_average), $l);

        }

        return $the_central_moment / $n;

    }

    private static function statisticSampleVariance($table, $x_average, $n)
    {

        $sample_variance = 0;

        foreach ($table as $t) {

            $sample_variance += $t['m'] * pow(($t['u'] - $x_average), 2);

        }

        return $sample_variance / ($n - 1);

    }

    private static function statisticAverage($table)
    {

        $x_average = 0;

        foreach ($table as $t) {

            $x_average += $t['h'] * $t['u'];

        }

        return $x_average;

    }

    private static function createStatisticTable($sampling, $K, $d, $n)
    {

        $table = [];

        $min = min($sampling);

        $sum_h = 0;

        $sum_m = 0;

        for ($i = 1; $i <= $K; $i++) {

            $m = 0;

            $table[$i]['min'] = $min + ($i - 1) * $d;

            $table[$i]['max'] = $table[$i]['min'] + $d;

            foreach ($sampling as $key => $item) {

                if ($i == 1) $cond = $item >= $table[$i]['min'];
                else $cond = $item > $table[$i]['min'];

                if (
                    $cond &&
                    ($item <= $table[$i]['max'] || bccomp($item, $table[$i]['max'], 2) == 0)
                ) {

                    $m++;

                    unset($sampling[$key]);

                }

            }

            $h = $m / $n;

            $sum_h += $h;

            $sum_m += $m;

            $table[$i]['m'] = $m;

            $table[$i]['h'] = $h;

            $table[$i]['sum_h'] = $sum_h;

            $table[$i]['u'] = ($table[$i]['min'] + $table[$i]['max']) / 2;


        }

        if ((isset($sum_m) && $sum_m != $n) || !empty($sampling)) return false;

        return $table;

    }

}
