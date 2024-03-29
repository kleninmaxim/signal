<?php

use Illuminate\Support\Facades\Route;


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

    //return view('welcome');

});

Route::get('/binance/tickers', [\App\Http\Controllers\BinanceController::class, 'allTickers']);

Route::get('/binance/ema/btc', [\App\Http\Controllers\BinanceController::class, 'emaBtc']);

Route::get('/tinkoff/tickers', [\App\Http\Controllers\TinkoffController::class, 'allTickers']);

Route::get('/openOrders', [\App\Http\Controllers\HineyController::class, 'getOpenOrders'])->name('get_open_orders');

Route::post('/storeSettings', [\App\Http\Controllers\HineyController::class, 'storeSettings'])->name('store_settings');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/percentage', [\App\Http\Controllers\PercentageController::class, 'percentage']);


    Route::get('/support/supportStrategy', [\App\Http\Controllers\SupportController::class, 'supportStrategy']);


    Route::get('/agressive/agressiveScalperStrategy', [\App\Http\Controllers\AgressiveScalperController::class, 'agressiveScalperStrategy']);


    Route::get('/percentage/shibOnePercentageStrategy', [\App\Http\Controllers\OnePercentageController::class, 'shibOnePercentageStrategy']);

    Route::get('/bollinger/test', [\App\Http\Controllers\BollingerController::class, 'test']);

    Route::get('/keltner/test', [\App\Http\Controllers\KeltnerChannelsController::class, 'test']);

    Route::get('/keltner/keltnerStrategy', [\App\Http\Controllers\KeltnerChannelsController::class, 'keltnerStrategy']);



    Route::get('/hiney/test', [\App\Http\Controllers\HineyController::class, 'test']);

    Route::get('/hiney/saveToFileContractsPrecisions', [\App\Http\Controllers\HineyController::class, 'saveToFileContractsPrecisions']);

    Route::get('/hiney/hineyStrategy', [\App\Http\Controllers\HineyController::class, 'hineyStrategy']);

    Route::get('/hiney/statisticBalance', [\App\Http\Controllers\HineyController::class, 'statisticBalance']);

    Route::get('/hiney/cancelOrderWherePairNotInPosition', [\App\Http\Controllers\HineyController::class, 'cancelOrderWherePairNotInPosition']);


    Route::get('/binance/loadCandles', [\App\Http\Controllers\BinanceController::class, 'loadCandles']);

    Route::get('/binance/updateCandles', [\App\Http\Controllers\BinanceController::class, 'updateCandles']);

    Route::get('/binance/coraWaveOld', [\App\Http\Controllers\BinanceController::class, 'coraWaveOld']);

    Route::get('/binance/coraWave', [\App\Http\Controllers\BinanceController::class, 'coraWave']);

    Route::get('/binance/ema', [\App\Http\Controllers\BinanceController::class, 'ema']);

    Route::get('/binance/test', [\App\Http\Controllers\BinanceController::class, 'test']);

    Route::get('/binance/testFinalStrategy', [\App\Http\Controllers\BinanceController::class, 'testFinalStrategy']);

    Route::get('/binance/testFivePercentageChangeStrategy', [\App\Http\Controllers\BinanceController::class, 'testFivePercentageChangeStrategy']);

    Route::get('/binance/processTokens', [\App\Http\Controllers\BinanceController::class, 'processTokens']);

    Route::get('/binance/testHineyStrategy', [\App\Http\Controllers\BinanceController::class, 'testHineyStrategy']);

    Route::get('/binance/count/{base}/{price}/{stop}', [\App\Http\Controllers\BinanceController::class, 'count']);

    Route::get('/binance/random', [\App\Http\Controllers\BinanceController::class, 'random']);

    Route::get('/binance/randomOnlyLong', [\App\Http\Controllers\BinanceController::class, 'randomOnlyLong']);

    Route::get('/binance/testFivePercentageChangeStrategyWithSell', [\App\Http\Controllers\BinanceController::class, 'testFivePercentageChangeStrategyWithSell']);


    Route::get('/tinkoff/coraWave', [\App\Http\Controllers\TinkoffController::class, 'coraWave']);

    Route::get('/tinkoff/loadCandles', [\App\Http\Controllers\TinkoffController::class, 'loadHourCandles']);

    Route::get('/tinkoff/loadDayWeekMonthCandles', [\App\Http\Controllers\TinkoffController::class, 'loadDayWeekMonthCandles']);

    Route::get('/tinkoff/test', [\App\Http\Controllers\TinkoffController::class, 'test']);

    Route::get('/tinkoff/saveDayCloseCandleTime', [\App\Http\Controllers\TinkoffController::class, 'saveDayCloseCandleTime']);

    Route::get('/tinkoff/commonStrategy', [\App\Http\Controllers\TinkoffController::class, 'commonStrategy']);

    Route::get('/tinkoff/topCompany', [\App\Http\Controllers\TinkoffController::class, 'topCompany']);

    Route::get('/tinkoff/processShares', [\App\Http\Controllers\TinkoffController::class, 'processShares']);

    Route::get('/tinkoff/testHineyMoneyFlow', [\App\Http\Controllers\TinkoffController::class, 'testHineyMoneyFlow']);



    Route::post(
        '/tinkoff/add-new-ticker',
        [\App\Http\Controllers\TinkoffController::class, 'addNewTicker']
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


    //old
    Route::get('/tinkoff/LoadAllTickers', [\App\Http\Controllers\TinkoffController::class, 'LoadAllTickers']);

});


