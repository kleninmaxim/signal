<?php

namespace App\Http\Controllers;

use App\Hiney\Strategies\OnePercentage;

class OnePercentageController extends Controller
{

    public function ethOnePercentageStrategy()
    {

        (new OnePercentage('ETHUSDT', 4, 1))->run();

    }

}
