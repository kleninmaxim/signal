<?php

namespace App\Http\Controllers;

use App\Hiney\BinanceFutures;
use App\Hiney\Src\Math;
use App\Hiney\Src\Telegram;
use App\Hiney\BinanceFuturesSocket;
use App\Models\OnePercentage;
use Illuminate\Support\Facades\Storage;

class OnePercentageController extends Controller
{

    private string $disk = 'local';

    private string $file = 'precisions.json';

    private float $profit = 5;

    public function onePercentageStrategy()
    {

        $pair = 'ETHUSDT';

        $telegram = new Telegram();

        $binance_futures = new BinanceFutures();

        // проверить, есть ли файл precisions
        if (!Storage::disk($this->disk)->exists($this->file))
            $this->saveToFileContractsPrecisions();

        // взять все пары к которым есть информация
        $precisions = json_decode(Storage::get($this->file), true);

        // взять информацию о текущей позиции
        if ($position = $binance_futures->getPositionInformation($pair)[0]) {

            // начало работы скрипта
            $hour_start = date('H');

            // подключение по сокету
            BinanceFuturesSocket::connect($pair);

            // модель для работы с сохранением уровней
            $one_percentage_model = OnePercentage::where('pair', 'ETHUSDT')->first();

            // если ее нет, то добавить в бд
            if (!$one_percentage_model)
                $one_percentage_model = OnePercentage::create([
                    'pair' => 'ETHUSDT',
                    'level' => 0,
                ]);

            // необходимый уровень для контроля изменений
            $level = $one_percentage_model->level;

            $do = true;

            while ($do) {

                if (in_array(date('s'), [0, 55]))
                    error_log(date('Y-m-d H:i:s') . '[INFO] work');

                // проверка актуален ли этот скрипт по времени
                if ($this->checkTime($hour_start)) {

                    if ($kline = BinanceFuturesSocket::run()) {

                        // event time is not very old
                        if (abs($kline['event_time'] / 1000 - time()) <= 5) {

                            // если позиция не открыта
                            if (empty($position) || $position['positionAmt'] == 0) {

                                // открываем позицию по умолчанию
                                $position = $this->action(
                                    $binance_futures,
                                    $pair,
                                    'BUY',
                                    $position,
                                    $kline,
                                    $precisions,
                                    $kline['close'] * 0.985,
                                    $telegram,
                                    true,
                                    false
                                );

                                $level = 0;

                                $one_percentage_model->level = $level;

                                $one_percentage_model->save();

                                $telegram->send('Open default position' . "\n");

                            } else {

                                $current_position = $position['positionAmt'] < 0 ? 'SELL' : 'BUY';

                                $change_price = ($current_position == 'SELL')
                                    ? ($position['entryPrice'] - $kline['close']) / $position['entryPrice'] * 100
                                    : ($kline['close'] - $position['entryPrice']) / $position['entryPrice'] * 100;

                                // проверка на вхождение цены в диапазон
                                if ($change_price <= $level - 1 + 0.01) {

                                    // закрыть позицию и открыть в противоположную сторону
                                    $position = $this->action(
                                        $binance_futures,
                                        $pair,
                                        $current_position,
                                        $position,
                                        $kline,
                                        $precisions,
                                        ($current_position == 'SELL') ? $kline['close'] * 0.985 : $kline['close'] * 1.015,
                                        $telegram,
                                        false
                                    );

                                    // обнулить позицию
                                    $level = 0;

                                    $one_percentage_model->level = $level;

                                    $one_percentage_model->save();

                                } elseif ($change_price >= $level + 1) {

                                    $level++; // увеличить уровень

                                    $one_percentage_model->level = $level;

                                    $one_percentage_model->save();

                                }

                            }

                        } else {

                            usleep(10000);

                            $telegram->send('Event time is not correct' . "\n");

                        }

                    }

                } else
                    $do = false;

            }

            BinanceFuturesSocket::close();

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

    private function action(
        $binance_futures,
        $pair,
        $current_position,
        $position,
        $kline,
        $precisions,
        $stop_loss,
        $telegram,
        $continue,
        $close_position = true
    )
    {

        // закрыть позицию
        if ($close_position)
            $binance_futures->createOrder(
                $pair,
                $this->changePosition($current_position),
                'MARKET',
                abs($position['positionAmt']),
                options: ['reduce_only' => 'true']
            );

        // закрываем ненужные открытые ордера
        if ($open_orders = $binance_futures->getAllOpenOrders($pair))
            foreach ($open_orders as $open_order)
                $binance_futures->cancelOrder($open_order['orderId'], $open_order['symbol']);

        $balance = $binance_futures->getBalances()['totalMarginBalance'];

        $amount = Math::round(
            $balance * $this->profit / $kline['close'],
            $precisions[$pair]['amount_precision']
        );

        $open_position = $continue ? $current_position : $this->changePosition($current_position);

        // открыть позицию
        if (
            $order = $binance_futures->createOrder(
                $pair,
                $open_position,
                'MARKET',
                $amount
            )
        ) {

            // поставить стоп лосс
            $stop_market = $binance_futures->createOrder(
                $pair,
                $this->changePosition($open_position),
                'STOP_MARKET',
                options: [
                    'stop_price' => Math::round($stop_loss, $precisions[$pair]['price_precision']),
                    'close_position' => 'true',
                    'working_type' => 'MARK_PRICE',
                ]
            );

            if (!$stop_market)
                $telegram->send($pair . ' Stop loss is not set!!!' . "\n"); // отправляет сообщение в телеграм о том, что стоп лосс не выставлен

            $new_position = $binance_futures->getPositionInformation($pair)[0];

            $telegram->send(
                '*' . $pair . '*' . "\n" .
                '*OPEN*' . "\n" .
                'Position: ' . $open_position . "\n" .
                'Entry price: ' . Math::round($new_position['entryPrice'], $precisions[$pair]['price_precision']) . "\n"
            );

            return $new_position;

        }


        $telegram->send($pair . ' [IMPORTANT!!!] For some reason order is not created!!! JSON: ' . $order . "\n"); // отправляет сообщение в телеграм об ошибке постановки ордера

        return false;

    }

    private function changePosition($position): string
    {

        return ($position == 'SELL') ? 'BUY' : 'SELL';

    }

    private function checkTime($hour_start): bool
    {

        return ($hour_start == date('H')) && (date('i') != 59 || date('s') < 58);

    }

}
