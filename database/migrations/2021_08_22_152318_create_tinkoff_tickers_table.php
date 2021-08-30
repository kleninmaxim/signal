<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTinkoffTickersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tinkoff_tickers', function (Blueprint $table) {
            $table->id();
            $table->string('figi')->unique();
            $table->string('ticker')->unique();
            $table->string('name')->unique();
            $table->string('type');
            $table->boolean('notify')->default(true);
            $table->boolean('short')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tinkoff_tickers');
    }
}
