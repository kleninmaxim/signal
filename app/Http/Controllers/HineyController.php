<?php

namespace App\Http\Controllers;

use App\Hiney\Binance;
use App\Hiney\BinanceFutures;
use App\Hiney\Src\Telegram;
use App\Hiney\Strategies\TheHineyMoneyFlow;
use App\Models\Statistic\Balance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HineyController extends Controller
{

    private string $disk = 'local';

    private string $file = 'precisions.json';

    public function test()
    {

        //debug((new BinanceFutures())->getContracts(), true);

        //debug((new BinanceFutures())->cancelOrder('4887726141', 'WAVESUSDT'), true);

        //debug((new BinanceFutures())->getOrderStatus('4887842772', 'WAVESUSDT'), true);

        //debug((new BinanceFutures())->getAllOpenOrders('WAVESUSDT'), true);

        //debug((new BinanceFutures())->getPositionInformation('WAVESUSDT'), true);

        //debug((new BinanceFutures())->createOrder('WAVESUSDT', 'BUY', 'MARKET', 0.5), true);

//        debug(
//            (new BinanceFutures())->createOrder(
//                'WAVESUSDT',
//                'SELL',
//                'STOP_MARKET',
//                options: [
//                    'stop_price' => 10,
//                    'close_position' => 'true'
//                ]
//            )
//        );

//        debug(
//            (new BinanceFutures())->createOrder(
//                'WAVESUSDT',
//                'SELL',
//                'TAKE_PROFIT_MARKET',
//                options: [
//                    'stop_price' => 20,
//                    'close_position' => 'true',
//                    'working_type' => 'MARK_PRICE'
//                ]
//            )
//        );

        //debug((new BinanceFutures())->createOrder('WAVESUSDT', 'BUY', 'LIMIT', 450, 10));


        //debug((new BinanceFutures())->getBalances(), true);

        $first_balance = Balance::orderBy('created_at', 'asc')->first()->toArray();
        $last_balance = Balance::orderBy('created_at', 'desc')->first()->toArray();

        $annual_apy = ($last_balance['total_margin_balance'] - $first_balance['total_margin_balance']) * 365 * 24 * 100 / ((Carbon::parse($first_balance['created_at'])->diffInHours(Carbon::parse($last_balance['created_at']))) * $first_balance['total_margin_balance']);
        debug('Annual APY: ' . $annual_apy);
        debug('USDT in day: ' . 5 * $annual_apy * $first_balance['total_margin_balance'] / (365 * 100));

    }

    public function hineyStrategy()
    {

        // пары
        $pairs = $this->getPairs();

        // таймфрейм
        $timeframe = '1h';

        // записать precisions в файл
        $this->saveToFileContractsPrecisions();

        // проверка на то, что файл этот есть
        if (Storage::disk($this->disk)->exists($this->file)) {

            // создание телеграм для отправки сообщения
            $telegram = new Telegram();

            // объект для взаимодействия с фьючерсами binance через API
            $binance_futures = new BinanceFutures();

            // берем текущий баланс
            if ($balances = $binance_futures->getBalances()) {

                // проверяет, удовлетворяет ли баланс минимальным требованиям
                if ($binance_futures->protectBalance($balances)) {

                    // взять все пары к которым есть информация
                    $precisions = json_decode(Storage::get($this->file), true);

                    // берем все торгующиеся рынки
                    $pairs_original = array_keys($precisions);

                    // берем рынки, которые находятся не в позиции
                    $pairs_not_in_position = $binance_futures->getPairsNotInPosition($balances);

                    // пройтись по всем заданным мною рынкам, убедиться, что они существуют
                    foreach ($pairs as $pair)
                        if (in_array($pair, $pairs_original)) {

                            // проверка позиции на рынке
                            if (in_array($pair, $pairs_not_in_position)) {

                                // создать экземпляр стратегии по свечам бинанса
                                $strategy = new TheHineyMoneyFlow(
                                    Binance::getCandles($pair, $timeframe, removeCurrent: true)
                                );

                                // запустить стратегию
                                if ($position = $strategy->runReversal()) {

                                    // закрываем ненужные открытые ордера
                                    if ($open_orders = $binance_futures->getAllOpenOrders($pair))
                                        foreach ($open_orders as $open_order)
                                            $binance_futures->cancelOrder($open_order['orderId'], $open_order['symbol']);

                                    // рассчет amount, который нужно для открытия позиции
                                    $position['amount'] = $strategy->countAmount();

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

                                        // поставить стоп лосс
                                        $stop_market = $binance_futures->createOrder(
                                            $pair,
                                            $strategy->reversePosition($position['position']),
                                            'STOP_MARKET',
                                            options: [
                                                'stop_price' => $position['stop_loss'],
                                                'close_position' => 'true',
                                                'working_type' => 'MARK_PRICE',
                                            ]
                                        );

                                        // поставить тейк профит
                                        $take_profit = $binance_futures->createOrder(
                                            $pair,
                                            $strategy->reversePosition($position['position']),
                                            'TAKE_PROFIT_MARKET',
                                            options: [
                                                'stop_price' => $position['take_profit'],
                                                'close_position' => 'true',
                                                'working_type' => 'MARK_PRICE',
                                            ]
                                        );

                                        if (!$stop_market)
                                            $telegram->send($pair . ' Stop loss is not set!!!' . "\n"); // отправляет сообщение в телеграм о том, что стоп лосс не выставлен

                                        if (!$take_profit)
                                            $telegram->send($pair . ' Take Profit is not set!!! JSON: ' . "\n"); // отправляет сообщение в телеграм о том, что тейк профит не выставлен

                                        // отправляет сообщение в телеграм о нашей позиции
                                        $telegram->send(
                                            $strategy->message($pair, $position, $timeframe)
                                        );

                                    } else
                                        $telegram->send($pair . ' For some reason order is not created!!! JSON: ' . $order . "\n"); // отправляет сообщение в телеграм об ошибке постановки ордера

                                }

                            }

                        } else
                            $telegram->send($pair . ' something wrong. It is no in precision file!!!' . "\n"); // отправляет сообщение в телеграм об ошибке

                } else
                    $telegram->send('Balance not protected!!!' . "\n"); // отправляет сообщение в телеграм о малом балансе

            } else
                $telegram->send('Can\'t get balance!!! Message: ' . $balances . "\n"); // отправляет сообщение в телеграм о непоступлении баланса

        }

    }

    public function statisticBalance()
    {

        if ($balances = (new BinanceFutures())->getBalances()) {

            Balance::create([
                'total_wallet_balance' => $balances['totalWalletBalance'],
                'total_unrealized_profit' => $balances['totalUnrealizedProfit'],
                'total_margin_balance' => $balances['totalMarginBalance'],
                'available_balance' => $balances['availableBalance'],
                'created_at' => Carbon::now()->format('Y-m-d H:00:00')
            ]);

            (new Telegram())->send(
                'BALANCE.' . "\n" .
                'Total Wallet Balance: ' . $balances['totalWalletBalance'] . "\n" .
                'Total Unrealized Profit: ' . $balances['totalUnrealizedProfit'] . "\n" .
                'Total Margin Balance: ' . $balances['totalMarginBalance'] . "\n" .
                'Total Available Balance: ' . $balances['availableBalance'] . "\n"
            ); // отправляет сообщение в телеграм о балансе

        }

    }

    public function cancelOrderWherePairNotInPosition()
    {

        // пары
        $pairs = $this->getPairs();

        // объект для взаимодействия с фьючерсами binance через API
        $binance_futures = new BinanceFutures();

        // берем текущий баланс, берем все рынке вне позиции, проходимся по всем рынкам, если пара не в позиции и есть открытые ордара - закрыть их
        if ($balances = $binance_futures->getBalances())
            if ($pairs_not_in_position = $binance_futures->getPairsNotInPosition($balances))
                foreach ($pairs as $pair)
                    if (in_array($pair, $pairs_not_in_position))
                        if ($open_orders = $binance_futures->getAllOpenOrders($pair))
                            foreach ($open_orders as $open_order)
                                $binance_futures->cancelOrder($open_order['orderId'], $open_order['symbol']);

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

    public function getOpenOrders()
    {

        $open_orders = Cache::remember('open_orders', 10, function () {
            return array_filter((new BinanceFutures())->getPositionInformation(), function($open_order) {
                return $open_order['notional'] != 0;
            });
        });

        return view('open-orders', compact('open_orders'));

    }

    private function getPairs(): array
    {

        return  [
            //'1INCHUSDT',
            'AAVEUSDT',
            //'ADAUSDT',
            'ALGOUSDT',
            'ANKRUSDT',
            'ARUSDT',
            'ATOMUSDT',
            'AUDIOUSDT',
            'AVAXUSDT',
            'AXSUSDT',
            'BATUSDT',
            'BCHUSDT',
            'BNBUSDT',
            'BTCUSDT',
            //'BTTUSDT',
            'CELOUSDT',
            'CHZUSDT',
            'COMPUSDT',
            'CRVUSDT',
            'DASHUSDT',
            'DOGEUSDT',
            'DOTUSDT',
            'EGLDUSDT',
            'ENJUSDT',
            'ENSUSDT',
            'EOSUSDT',
            'ETCUSDT',
            'ETHUSDT',
            'FILUSDT',
            'FTMUSDT',
            'GALAUSDT',
            'GRTUSDT',
            'HBARUSDT',
            'HNTUSDT',
            'HOTUSDT',
            'ICPUSDT',
            //'ICXUSDT',
            //'IOTXUSDT',
            'KLAYUSDT',
            'KSMUSDT',
            'LINKUSDT',
            'LPTUSDT',
            'LRCUSDT',
            'LTCUSDT',
            'LUNAUSDT',
            'MANAUSDT',
            'MATICUSDT',
            //'MKRUSDT',
            'NEARUSDT',
            'NEOUSDT',
            'OMGUSDT',
            'QTUMUSDT',
            'RUNEUSDT',
            'RVNUSDT',
            'SANDUSDT',
            'SCUSDT',
            'SOLUSDT',
            //'SUSHIUSDT',
            //'THETAUSDT',
            'TRXUSDT',
            //'UNIUSDT',
            'VETUSDT',
            'WAVESUSDT',
            //'XEMUSDT',
            'XLMUSDT',
            'XMRUSDT',
            //'XRPUSDT',
            //'XTZUSDT',
            'ZECUSDT',
            //'ZENUSDT',
            'ZILUSDT',
        ];

    }

    /*

    LIMIT

    Array
    (
        [orderId] => 4887710177
        [symbol] => WAVESUSDT
        [status] => NEW
        [clientOrderId] => QRZTARXf9vKQd1iwzGyqUK
        [price] => 10
        [avgPrice] => 0.00000
        [origQty] => 10
        [executedQty] => 0
        [cumQty] => 0
        [cumQuote] => 0
        [timeInForce] => GTC
        [type] => LIMIT
        [reduceOnly] =>
        [closePosition] =>
        [side] => BUY
        [positionSide] => BOTH
        [stopPrice] => 0
        [working_type] => CONTRACT_PRICE
        [priceProtect] =>
        [origType] => LIMIT
        [updateTime] => 1640719229063
    )
    */

    /*

    GET ORDER STATUS

    Array
    (
        [orderId] => 4887710177
        [symbol] => WAVESUSDT
        [status] => NEW
        [clientOrderId] => QRZTARXf9vKQd1iwzGyqUK
        [price] => 10
        [avgPrice] => 0.00000
        [origQty] => 10
        [executedQty] => 0
        [cumQuote] => 0
        [timeInForce] => GTC
        [type] => LIMIT
        [reduceOnly] =>
        [closePosition] =>
        [side] => BUY
        [positionSide] => BOTH
        [stopPrice] => 0
        [working_type] => CONTRACT_PRICE
        [priceProtect] =>
        [origType] => LIMIT
        [time] => 1640719229063
        [updateTime] => 1640719229063
    )
    */

    /*

    CANCEL ORDER

    Array
    (
        [orderId] => 4887710177
        [symbol] => WAVESUSDT
        [status] => CANCELED
        [clientOrderId] => QRZTARXf9vKQd1iwzGyqUK
        [price] => 10
        [avgPrice] => 0.00000
        [origQty] => 10
        [executedQty] => 0
        [cumQty] => 0
        [cumQuote] => 0
        [timeInForce] => GTC
        [type] => LIMIT
        [reduceOnly] =>
        [closePosition] =>
        [side] => BUY
        [positionSide] => BOTH
        [stopPrice] => 0
        [working_type] => CONTRACT_PRICE
        [priceProtect] =>
        [origType] => LIMIT
        [updateTime] => 1640719301118
    )

    Array
    (
        [code] => -2011
        [msg] => Unknown order sent.
    )
    */

}
