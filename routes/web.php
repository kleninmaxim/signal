<?php

use Illuminate\Support\Facades\Route;

use \jamesRUS52\TinkoffInvest\TIClient;
use \jamesRUS52\TinkoffInvest\TISiteEnum;
use \jamesRUS52\TinkoffInvest\TIException;

function debug($arr, $die = false)
{

    echo '<pre>' . print_r($arr, true) . '</pre>';

    if ($die) die;

}

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {

    return view('welcome');

    $client = new TIClient(config('api.tinkoff_token'), TISiteEnum::EXCHANGE);

    $stockes = $client->getStocks(['V', 'LKOH']);

    try {

        $instr = $client->getInstrumentByTicker('TSLA');

        debug($instr, true);

    } catch (TIException $e) {

        dump('there are no ticker');
    };

    return view('welcome');
});

Route::get('/telegram', [\App\Http\Controllers\TelegramBotController::class, 'telegram']);

Route::middleware(['auth:sanctum', 'verified'])->group(function () {


    Route::get('/tinkoff/loadCandles', [\App\Http\Controllers\TinkoffController::class, 'loadCandles']);

    Route::get('/binance/getCandles/{pair}/{timeframe}', [\App\Http\Controllers\BinanceController::class, 'getCandles']);

    Route::get('/tinkoff/notifyHourStrategies', [\App\Http\Controllers\TinkoffController::class, 'notifyHourStrategies']);

    Route::get('/binance/notifyHourStrategies', [\App\Http\Controllers\BinanceController::class, 'notifyHourStrategies']);

    Route::post(
        '/tinkoff/add-new-ticker', [\App\Http\Controllers\TinkoffController::class, 'addNewTicker']
    )->name('tinkoff_add_new_ticker_post');


    Route::get('/tinkoff', function () {
        return view('tinkoff.main');
    })->name('tinkoff_main');

    Route::get('/tinkoff/add-new-ticker', function () {
        return view('tinkoff.add-new-ticker');
    })->name('tinkoff_add_new_ticker');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    

});


