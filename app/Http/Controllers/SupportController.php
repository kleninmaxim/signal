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
        $pair = 'BTCUSDT';

        // long or short
        $buy_or_sell = 'BUY';

        // записать precisions в файл
        if (!Storage::disk($this->disk)->exists($this->file))
            $this->saveToFileContractsPrecisions();

        // объект для взаимодействия с фьючерсами binance через API
        $binance_futures = new BinanceFutures();

        // создать экземпляр стратегии по свечам бинанса, включая текущую свечу
        $strategy = new Support(
            $binance_futures->getCandles($pair, $timeframe),
            $buy_or_sell
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

                    // округли все значения в соответсвии с биржей по precisions из файла
                    $strategy->round($position, $precisions[$pair]);


                    debug($position, true);

                    // если ордер поставился
                    if (
                        $order = $binance_futures->createOrder(
                            $pair,
                            $position['position'],
                            'MARKET',
                            $position['amount']
                        )
                    ) {

                        // поставить стоп лосс
                        $stop_market = $binance_futures->createOrder(
                            $pair,
                            $position['position'],
                            'STOP_MARKET',
                            options: [
                                'stop_price' => $position['stop_loss'],
                                'close_position' => 'true',
                                'working_type' => 'MARK_PRICE',
                            ]
                        );

                        //if (!$stop_market)
                            //$telegram->send($pair . ' Stop loss is not set!!!' . "\n"); // отправляет сообщение в телеграм о том, что стоп лосс не выставлен

                        // отправляет сообщение в телеграм о нашей позиции
//                        $telegram->send(
//                            $strategy->message($pair, $position, $timeframe)
//                        );

                    } //else
                        //$telegram->send($pair . ' For some reason order is not created!!! JSON: ' . $order . "\n"); // отправляет сообщение в телеграм об ошибке постановки ордера

                } else {
                    //
                }

            }

        } else {
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
