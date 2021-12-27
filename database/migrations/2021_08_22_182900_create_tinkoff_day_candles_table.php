<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTinkoffDayCandlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tinkoff_day_candles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tinkoff_ticker_id');
            $table->float('open', 25, 8);
            $table->float('close', 25, 8);
            $table->float('high', 25, 8);
            $table->float('low', 25, 8);
            $table->float('volume', 25, 8);
            $table->timestamp('time_start');

            $table->foreign('tinkoff_ticker_id')->references('id')->on('tinkoff_tickers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tinkoff_day_candles');
    }
}
