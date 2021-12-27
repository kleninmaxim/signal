<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTinkoffStrategyOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tinkoff_strategy_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tinkoff_ticker_id');
            $table->foreignId('strategy_id');
            $table->string('timeframe');
            $table->json('options');

            $table->foreign('tinkoff_ticker_id')->references('id')->on('tinkoff_tickers')->onDelete('cascade');
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
        Schema::dropIfExists('tinkoff_strategy_options');
    }
}
