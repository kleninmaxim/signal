<?php

namespace App\Http\Controllers;

use App\Hiney\Binance;
use App\Hiney\BinanceFutures;
use App\Hiney\Src\Telegram;
use App\Hiney\Strategies\TheHineyMoneyFlow;
use Illuminate\Support\Facades\Storage;

class HineyController extends Controller
{

    private string $disk = 'local';

    private string $file = 'precisions.json';

    public function test()
    {

        //debug((new BinanceFutures())->getBalances(), true);

        //debug((new BinanceFutures())->getContracts(), true);

        //debug((new BinanceFutures())->cancelOrder('4887726141', 'WAVESUSDT'), true);

        //debug((new BinanceFutures())->getOrderStatus('4887842772', 'WAVESUSDT'), true);

        //debug((new BinanceFutures())->getAllOpenOrders('WAVESUSDT'), true);

        //debug((new BinanceFutures())->getPositionInformation('WAVESUSDT'), true);

        //debug((new BinanceFutures())->createOrder('WAVESUSDT', 'BUY', 'MARKET', 0.5), true);

        //debug((new BinanceFutures())->createOrder('WAVESUSDT', 'SELL', 'STOP_MARKET', stop_price: 10, close_position: 'true'));

        //debug((new BinanceFutures())->createOrder('WAVESUSDT', 'SELL', 'TAKE_PROFIT_MARKET', stop_price: 20, close_position: 'true', workingType: 'MARK_PRICE'));

        //debug((new BinanceFutures())->createOrder('WAVESUSDT', 'BUY', 'LIMIT', 450, 10));


        //debug((new BinanceFutures())->getBalances(), true);

/*        $pair = 'BTC/USDT';

        $pair_str = str_replace('/', '', $pair);

        debug(Binance::getCandles($pair_str, '5m'));*/

    }

    public function hineyStrategy()
    {

        // пары
        $pairs = [
            '1INCHUSDT',
            'AAVEUSDT',
            'ADAUSDT',
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
            'BTTUSDT',
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
            'IOTXUSDT',
            'KLAYUSDT',
            'KSMUSDT',
            'LINKUSDT',
            'LPTUSDT',
            'LRCUSDT',
            'LTCUSDT',
            'LUNAUSDT',
            'MANAUSDT',
            'MATICUSDT',
            'MKRUSDT',
            'NEARUSDT',
            'NEOUSDT',
            'OMGUSDT',
            'QTUMUSDT',
            'RUNEUSDT',
            'RVNUSDT',
            'SANDUSDT',
            'SCUSDT',
            'SOLUSDT',
            'SUSHIUSDT',
            'THETAUSDT',
            'TRXUSDT',
            'UNIUSDT',
            'VETUSDT',
            'WAVESUSDT',
            'XEMUSDT',
            'XLMUSDT',
            'XMRUSDT',
            'XRPUSDT',
            'XTZUSDT',
            'ZECUSDT',
            'ZENUSDT',
            'ZILUSDT',
        ];

        // таймфрейм
        $timeframe = '5m';

        // записать precisions в файл
        $this->saveToFileContractsPrecisions();

        // проверка на то, что файл этот есть
        if (Storage::disk($this->disk)->exists($this->file)) {

            // создание телеграм для отправки сообщения
            $telegram = new Telegram(
                config('api.telegram_token_binance'),
                config('api.telegram_user_id')
            );

            // объект для взаимодействия с фьючерсами binance через API
            $binance_futures = new BinanceFutures();

            // берем текущий баланс
            $balances = $binance_futures->getBalances();

            // если баланс пришел корректно
            if ($balances) {

                // взять все пары к которым есть информация
                $precisions = json_decode(Storage::get($this->file), true);

                // взять все торгующиеся пары
                $pairs_original = array_keys($precisions);

                // объявляем начальное положение рынков не в позиции
                $pairs_not_in_position = [];

                // проходимся по всем позициям и смотрим какие рынке находятся не в позиции
                foreach ($balances['positions'] as $balance)
                    if ($balance['notional'] == 0 ) $pairs_not_in_position[] = $balance['symbol'];

                // пройтись по всем заданным мною рынкам, убедиться, что они существуют и не находятся в позиции и переходить к стратегии
                foreach ($pairs as $pair)
                    if (in_array($pair, $pairs_original) && in_array($pair, $pairs_not_in_position)) {

                        // создать экземпляр стратегии по свечам бинанса
                        $strategy = new TheHineyMoneyFlow(
                            Binance::getCandles($pair, $timeframe, removeCurrent: true)
                        );

                        // запустить стратегию
                        $position = $strategy->run();

                        // если появилась возможность открыть позицию
                        if ($position) {

                            // посчитай amount, который нужно для открытия позиции
                            $position['amount'] = $strategy->countAmount();

                            // округли все значения в соответсвии с биржей по precisions из файла
                            $strategy->round($position, $precisions[$pair]);

                            // отправляет сообщение в телеграм о нашей позиции
                            $telegram->send(
                                $strategy->message($pair, $position, $timeframe)
                            );

                        }

                    } else
                        $telegram->send($pair . ' is in position or something wrong!!!' . "\n"); // отправляет сообщение в телеграм об ошибке

            } else
                $telegram->send('Can\'t get balance!!!' . "\n"); // отправляет сообщение в телеграм о непоступлении баланса

        }

    }

    // сохраняет precisions в файл
    public function saveToFileContractsPrecisions()
    {

        foreach ((new BinanceFutures())->getContracts()['symbols'] as $contract)
            if ($contract['contractType'] == 'PERPETUAL' && str_contains($contract['symbol'], 'USDT'))
                $precisions[$contract['symbol']] = [
                    'amount_precision' => $contract['quantityPrecision'],
                    'price_precision' => $contract['pricePrecision'],
                ];

        Storage::disk($this->disk)->put($this->file, json_encode($precisions ?? []));

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
    [workingType] => CONTRACT_PRICE
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
    [workingType] => CONTRACT_PRICE
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
    [workingType] => CONTRACT_PRICE
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
