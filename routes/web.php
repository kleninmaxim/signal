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

});

Route::get('/binance/tickers', [\App\Http\Controllers\BinanceController::class, 'allTickers']);

Route::get('/binance/ema/btc', [\App\Http\Controllers\BinanceController::class, 'emaBtc']);

Route::get('/tinkoff/tickers', [\App\Http\Controllers\TinkoffController::class, 'allTickers']);

Route::middleware(['auth:sanctum', 'verified'])->group(function () {


    Route::get('/binance/loadCandles', [\App\Http\Controllers\BinanceController::class, 'loadCandles']);

    Route::get('/binance/coraWave', [\App\Http\Controllers\BinanceController::class, 'coraWave']);

    Route::get('/binance/ema', [\App\Http\Controllers\BinanceController::class, 'ema']);

    Route::get('/binance/test', [\App\Http\Controllers\BinanceController::class, 'test']);



    Route::get('/tinkoff/coraWave', [\App\Http\Controllers\TinkoffController::class, 'coraWave']);

    Route::get('/tinkoff/loadCandles', [\App\Http\Controllers\TinkoffController::class, 'loadHourCandles']);

    Route::get('/tinkoff/loadDayWeekMonthCandles', [\App\Http\Controllers\TinkoffController::class, 'loadDayWeekMonthCandles']);

    Route::get('/tinkoff/test', [\App\Http\Controllers\TinkoffController::class, 'test']);

    Route::get('/tinkoff/volumeFiveMinute', [\App\Http\Controllers\TinkoffController::class, 'volumeFiveMinute']);


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


