<?php

use Illuminate\Support\Facades\DB;

require_once dirname(__DIR__) . '/public/index.php';

while (true) {

    sleep(10);

    //$level = \App\Models\OnePercentage::where('pair', 'ETHUSDT')->first()->toArray()['level'];
    $level = DB::table('one_percentages')->where('pair', 'ETHUSDT')->first();

    print_r($level); echo PHP_EOL;

}
