<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBinanceStrategyOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('binance_strategy_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('binance_pair_id');
            $table->foreignId('strategy_id');
            $table->string('timeframe');
            $table->json('options');

            $table->foreign('binance_pair_id')->references('id')->on('binance_pairs')->onDelete('cascade');
            $table->foreign('strategy_id')->references('id')->on('strategies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('binance_strategy_options');
    }
}
