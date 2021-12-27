<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStrategyDefaultOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('strategy_default_options', function (Blueprint $table) {
            $table->id();
            $table->string('exchange');
            $table->foreignId('strategy_id');
            $table->string('timeframe');
            $table->json('options');

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
        Schema::dropIfExists('strategy_default_options');
    }
}
