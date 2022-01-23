<?php

namespace App\Http\Controllers;

use App\Hiney\BinanceFutures;
use App\Hiney\Strategies\Support;
use Illuminate\Support\Facades\Storage;

class SupportController extends Controller
{

    private string $disk = 'local';

    private string $file = 'precisions.json';

    public function supportStrategy()
    {

        $start = microtime(true);

        // таймфрейм
        $timeframe = '5m';

        // рынок
        $pair = 'BTC';

        // long or short
        $buy_or_sell = 'SELL'; // BUY SELL

        // изменение цены
        $percentage_change = 0.5;

        // ATR
        $atr_parameter = 2;


        $pair = $pair . 'USDT';

        // записать precisions в файл
        if (!Storage::disk($this->disk)->exists($this->file))
            $this->saveToFileContractsPrecisions();

        // объект для взаимодействия с фьючерсами binance через API
        $binance_futures = new BinanceFutures();

        // создать экземпляр стратегии по свечам бинанса, включая текущую свечу
        $strategy = new Support(
            $binance_futures->getCandles($pair, $timeframe),
            $buy_or_sell,
            $atr_parameter
        );

        // запустить стратегию
        if ($position = $strategy->run()) {

            // берем текущий баланс
            if ($balances = $binance_futures->getBalances()) {

                // взять все пары к которым есть информация
                $precisions = json_decode(Storage::get($this->file), true);

                // берем все торгующиеся рынки
                $pairs_original = array_keys($precisions);

                if (in_array($pair, $pairs_original)) {

                    // рассчет amount, который нужно для открытия позиции
                    $position['amount'] = $strategy->countAmount($balances['totalMarginBalance']);

                    // если хочу поставить ордер в зависимости от процента изменения
                    if (isset($percentage_change)) {

                        $position = [
                            'position' => $buy_or_sell,
                            'price' => $position['price'],
                            'stop_loss' => ($buy_or_sell == 'SELL')
                                ? $position['price'] * (1 + $percentage_change / 100)
                                : $position['price'] * (1 - $percentage_change / 100),
                            'amount' => $balances['totalMarginBalance'] * 5 / ($percentage_change * $position['price'])
                        ];

                    }

                    // округли все значения в соответсвии с биржей по precisions из файла
                    $strategy->round($position, $precisions[$pair]);


                    // если ордер поставился
                    if (
                        $order = $binance_futures->createOrder(
                            $pair,
                            $position['position'],
                            'MARKET',
                            $position['amount']
                        )
                    ) {

                        debug($position);

                    } //else
                        //$telegram->send($pair . ' For some reason order is not created!!! JSON: ' . $order . "\n"); // отправляет сообщение в телеграм об ошибке постановки ордера

                } else {
                    //
                }

            }

        } else {
            debug('Strategy not allowed you get into position');
            //
        }

    }

    // сохраняет precisions в файл
    public function saveToFileContractsPrecisions()
    {

        $symbols = (new BinanceFutures())->getContracts()['symbols'];

        if (isset($symbols[0]['symbol'])) {

            foreach ($symbols as $contract)
                if ($contract['contractType'] == 'PERPETUAL' && str_contains($contract['symbol'], 'USDT'))
                    $precisions[$contract['symbol']] = [
                        'amount_precision' => $contract['quantityPrecision'],
                        'price_precision' => $contract['pricePrecision'],
                    ];

            Storage::disk($this->disk)->put($this->file, json_encode($precisions ?? []));

        }

    }

}
