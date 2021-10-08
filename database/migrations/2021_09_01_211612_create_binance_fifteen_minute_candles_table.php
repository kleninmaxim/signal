<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBinanceFifteenMinuteCandlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('binance_fifteen_minute_candles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('binance_pair_id');
            $table->float('open', 25, 8);
            $table->float('close', 25, 8);
            $table->float('high', 25, 8);
            $table->float('low', 25, 8);
            $table->float('volume', 25, 8);
            $table->timestamp('time_start');

            $table->foreign('binance_pair_id')->references('id')->on('binance_pairs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('binance_fifteen_minute_candles');
    }
}
