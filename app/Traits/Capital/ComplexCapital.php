<?php

namespace App\Traits\Capital;

use App\Src\Math;

trait ComplexCapital
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
    public static function complex($data)
    {

        if (count($data) >= 5) {

            self::deleteNotActualSignalsComplex($data); // удаление невыполнимых/неактуальных сделок

            $deals = self::getDealsComplex($data); // получение массива сделок

            $indicators = self::getIndicatorsComplex($deals); // получение основных параметров

            self::getParametersComplex($indicators); // получение дополнительных параметров

        }

        return [
            'deals' => $deals ?? null,
            'indicators' => $indicators ?? null,
            'final' => isset($indicators) ? self::finalParametersComplex($indicators, $deals ?? null) : null, // получение суммарных показателей
        ];

    }

    private static function finalParametersComplex($indicators, $deals)
    {

        $price_change = 1;

        $minute_sum = 0;

        $first = array_shift($deals);
        $last = array_pop($deals);

        $minutes = Math::diffInMinutes($first['time_buy'], $last['time_sell']);

        foreach ($indicators as $indicator) {

            $price_change *= $indicator['price_change'];

            $minute_sum += $indicator['minutes'];

        }

        $profit_percentage_sum = round(($price_change - 1) * 100, 2);

        return [
            'profit_percentage_sum' => $profit_percentage_sum,
            'days' => Math::minuteToDays($minute_sum),
            'profit_percentage_apy_sum' => Math::annualApy($profit_percentage_sum, $minute_sum)
        ];

    }

    private static function getParametersComplex(&$indicators)
    {

        foreach ($indicators as $key => $indicator) {

            $indicators[$key]['profit_percentage_apy'] = Math::annualApy(
                $indicator['profit_percentage'],
                $indicator['minutes']
            );

        }

    }

    private static function getIndicatorsComplex($deals)
    {

        $indicators = [];

        foreach ($deals as $deal) {

            $profit_percentage = Math::percentage($deal['buy'], $deal['sell']);

            $indicators[] = [
                'minutes' => Math::diffInMinutes($deal['time_buy'], $deal['time_sell']),
                'profit_percentage' => $profit_percentage,
                'price_change' => Math::change($deal['buy'], $deal['sell'])
            ];

            echo $profit_percentage . PHP_EOL;

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
    private static function getDealsComplex($data)
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

    private static function deleteNotActualSignalsComplex(&$data)
    {

        if ($data[0]['signal'] == 'short') array_shift($data);

        $last = array_pop($data);

        if ($last['signal'] == 'short') $data[] = $last;

    }

}
