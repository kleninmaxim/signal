<?php

namespace App\Traits\Capital;

use App\Src\Math;

trait SimpleCapital
{

    /*
     * INPUT: Необходим массив в следующем виде
        Array
        (
            [0] => Array
                (
                    [date] => 2018-11-01 03:00:00
                    [close] => 4041.32
                    [signal] => short
                )

            [1] => Array
                (
                    [date] => 2019-04-01 03:00:00
                    [close] => 5320.81
                    [signal] => long
                )
        )
    */
    public static function simple($data)
    {

        if (count($data) >= 5) {

            self::deleteNotActualSignalsSimple($data); // удаление невыполнимых/неактуальных сделок

            $deals = self::getDealsSimple($data); // получение массива сделок

            $indicators = self::getIndicatorsSimple($deals); // получение основных параметров

            self::getParametersSimple($indicators); // получение дополнительных параметров

        }

        return [
            'deals' => $deals ?? null,
            'indicators' => $indicators ?? null,
            'final' => isset($indicators) ? self::finalParametersSimple($indicators, $deals ?? null) : null, // получение суммарных показателей
        ];

    }

    private static function finalParametersSimple($indicators, $deals)
    {

        $profit_percentage_sum = 0;

        $minute_sum = 0;

        $negative = 0;

        $positive = 0;

        $i = 0;

        $j = 0;

        $n = 0;

        $p = 0;

        $sequence_of_negative = [];

        $sequence_of_positive = [];

        $first = array_shift($deals);
        $last = array_pop($deals);

        $minutes = Math::diffInMinutes($first['time_buy'], $last['time_sell']);

        foreach ($indicators as $indicator) {

            $profit_percentage_sum += $indicator['profit_percentage'];

            $minute_sum += $indicator['minutes'];

            if ($indicator['profit_percentage'] <= 0) {

                $negative += $indicator['profit_percentage'];

                $i++;

                $n++;

                if ($j != 0) $sequence_of_positive[] = $j;

                $j = 0;

            } else {

                if ($i != 0) $sequence_of_negative[] = $i;

                $positive += $indicator['profit_percentage'];

                $j++;

                $p++;

                $i = 0;
            }

        }

        if ($j != 0) $sequence_of_positive[] = $j;

        if ($i != 0) $sequence_of_negative[] = $i;

        $sequence_of_negative = array_count_values($sequence_of_negative);
        $sequence_of_positive = array_count_values($sequence_of_positive);

        ksort($sequence_of_negative);
        ksort($sequence_of_positive);

        return [
            'profit_percentage_sum' => $profit_percentage_sum,
            'days' => Math::minuteToDays($minutes),
            'profit_percentage_apy_sum' => Math::annualApy($profit_percentage_sum, $minutes),
            'sequence_of_negative' => $sequence_of_negative,
            'sequence_of_positive' => $sequence_of_positive,
            'average_negative' => ($n != 0) ? $negative / $n : 0,
            'average_positive' => ($p != 0) ? $positive / $p : 0,
        ];

    }

    private static function getParametersSimple(&$indicators)
    {

        foreach ($indicators as $key => $indicator) {

            $indicators[$key]['profit_percentage_apy'] = Math::annualApy(
                $indicator['profit_percentage'],
                $indicator['minutes']
            );

        }

    }

    private static function getIndicatorsSimple($deals)
    {

        $indicators = [];

        foreach ($deals as $deal) {

            $indicators[] = [
                'minutes' => Math::diffInMinutes($deal['time_buy'], $deal['time_sell']),
                'profit_percentage' => Math::percentage($deal['buy'], $deal['sell'])
            ];

        }

        return $indicators;

    }

    /*
     * OUTPUT: Отдает массив следующего вида
        Array
        (
            [0] => Array
                (
                    [buy] => 5320.81
                    [sell] => 8289.34
                    [time_buy] => 2019-04-01 03:00:00
                    [time_sell] => 2019-09-01 03:00:00
                )

            [1] => Array
                (
                    [buy] => 8523.61
                    [sell] => 6410.44
                    [time_buy] => 2020-02-01 03:00:00
                    [time_sell] => 2020-03-01 03:00:00
                )
        )
    */
    private static function getDealsSimple($data)
    {

        $deals = [];

        foreach ($data as $d) {

            if ($d['signal'] == 'long') {

                $buy = $d['close'];

                $time_buy = $d['date'];

                continue;

            }elseif ($d['signal'] == 'short') {

                $sell = $d['close'];

                $time_sell = $d['date'];

            }

            $deals[] = [
                'buy' => $buy ?? null,
                'sell' => $sell ?? null,
                'time_buy' => $time_buy ?? null,
                'time_sell' => $time_sell ?? null,
            ];

        }

        return $deals;

    }

    private static function deleteNotActualSignalsSimple(&$data)
    {

        if ($data[0]['signal'] == 'short') array_shift($data);

        $last = array_pop($data);

        if ($last['signal'] == 'short') $data[] = $last;

    }

}
