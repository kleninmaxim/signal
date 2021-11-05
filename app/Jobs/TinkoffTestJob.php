<?php

namespace App\Jobs;

use App\Models\TinkoffTicker;
use App\Src\Capital;
use App\Src\Strategy;
use App\Src\Tinkoff;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TinkoffTestJob implements ShouldQueue
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

        //$tickers = TinkoffTicker::all();
        //$tickers = TinkoffTicker::skip(0)->take(500)->get();
        $tickers = TinkoffTicker::where([
            ['short', 1],
            ['type', 'Stock'],
        ])->get();

        $sum = 0;
        $sum_apy = 0;
        $day = 1;
        $real_apy_sum = 0;
        $shares = 0;

        $download = 0;
        $count = count($tickers);

        foreach ($tickers as $ticker) {

            debug(PHP_EOL . round($download / $count * 100, 2) . PHP_EOL);

            $result = $this->rule($ticker->ticker);

            if ($result['final'] != null) {
                //debug($pair->pair);
                //debug($result['final']);
                $sum += $result['final']['profit_percentage_sum'];
                $sum_apy += $result['final']['profit_percentage_apy_sum'];
                $day = max($day, $result['final']['days']);

                if ($result['final']['days'] >= 365) {

                    $real_apy = (pow(($result['final']['profit_percentage_sum'] / 100 + 1), 365 / $result['final']['days']) - 1) * 100;

                    $shares++;

                } else {

                    $real_apy = 0;

                }

                $real_apy_sum += $real_apy;
            }

            $download++;

        }

        /*        debug($sum / $count);
                debug($day);
                debug($sum / $count * 365 / $day);
                debug($sum_apy / $count);*/

        $shares = ($shares != 0) ? $shares : 1;

        Log::info(
            "\n TINKOFF " . $this->strategy . ' ' . $this->capital . ' ' . $this->timeframe . ' ' .  $this->length . ' ' . "\n" .
            'I: ' . $sum / $count . "\n" .
            'Days: ' . $day . "\n" .
            'APY: ' . $sum / $count * 365 / $day . "\n" .
            'Sum APY: ' . $sum_apy / $count . "\n" .
            'Real APY: ' . $real_apy_sum / $shares . "\n\n"
        );

    }

    private function rule($ticker): ?array
    {

        switch ($this->strategy) {

            case 'simple':
                $strategy = Strategy::coraWaveSimple(
                    (new Tinkoff())->getCandles($ticker, $this->timeframe),
                    $this->length
                );
                break;
            case 'quick':
                $strategy = Strategy::coraWaveQuick(
                    (new Tinkoff())->getCandles($ticker, $this->timeframe),
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
