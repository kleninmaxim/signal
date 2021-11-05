<?php

namespace App\Jobs;

use App\Models\BinancePair;
use App\Src\Binance;
use App\Src\Capital;
use App\Src\Strategy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BinanceTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $timeframe;
    private $length;
    private $strategy;
    private $capital;

    /**
     * Create a new job instance.
     *
     * @return void
     *
     */
    public function __construct($timeframe, $length, $strategy, $capital)
    {
        $this->timeframe = $timeframe;
        $this->length = $length;
        $this->strategy = $strategy;
        $this->capital = $capital;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $pairs = BinancePair::all();
        $pairs = BinancePair::where('pair', 'BTC/USDT')->get();

        $sum = 0;
        $sum_apy = 0;
        $real_apy_sum = 0;
        $day = 1;

        $download = 0;
        $count = count($pairs);

        foreach ($pairs as $pair) {

            //debug(PHP_EOL . round($download / $count * 100, 2) . PHP_EOL);

            $result = $this->rule($pair->pair);

            if ($result['final'] != null) {
                debug(PHP_EOL . $pair->pair . PHP_EOL);
                //debug($result['final']);
                $sum += $result['final']['profit_percentage_sum'];
                $sum_apy += $result['final']['profit_percentage_apy_sum'];
                $day = max($day, $result['final']['days']);

                if ($result['final']['days'] >= 365) {

                    $real_apy = (pow(($result['final']['profit_percentage_sum'] / 100 + 1), 365 / $result['final']['days']) - 1) * 100;

                } else {

                    $real_apy = 0;

                }

                debug(PHP_EOL . $real_apy . ' | ' . $result['final']['profit_percentage_sum'] . PHP_EOL);

                $real_apy_sum += $real_apy;

            }

            $download++;

        }

/*        debug($sum / $count);
        debug($day);
        debug($sum / $count * 365 / $day);
        debug($sum_apy / $count);*/

        Log::info(
            "\n" . $this->strategy . ' ' . $this->capital . ' ' . $this->timeframe . ' ' .  $this->length . ' ' . "\n" .
            'I: ' . $sum / $count . "\n" .
            'Days: ' . $day . "\n" .
            'APY: ' . $sum / $count * 365 / $day . "\n" .
            'Sum APY: ' . $sum_apy / $count . "\n" .
            'Real APY: ' . $real_apy_sum / $count . "\n\n"
        );

    }

    private function rule($pair): ?array
    {

        switch ($this->strategy) {

            case 'simple':
                $strategy = Strategy::coraWaveSimple(
                    (new Binance())->getCandles($pair, $this->timeframe),
                    $this->length
                );
                break;
            case 'quick':
                $strategy = Strategy::coraWaveQuick(
                    (new Binance())->getCandles($pair, $this->timeframe),
                    $this->length
                );
                break;

        }

        if (isset($strategy)) {

            switch ($this->capital) {

                case 'simple':
                    $capital = Capital::simple($strategy);
                    break;

                case 'complex':
                    $capital = Capital::complex($strategy);
                    break;

            }

        }

        return $capital;

    }

}
