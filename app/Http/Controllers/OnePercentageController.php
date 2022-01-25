<?php

namespace App\Http\Controllers;

use App\Hiney\Strategies\OnePercentage;

class OnePercentageController extends Controller
{

    public function ethOnePercentageStrategy()
    {

        (new OnePercentage('ETHUSDT', 4, 1))->run();

    }

    public function shibOnePercentageStrategy()
    {

        (new OnePercentage('1000SHIBUSDT', 1, 1))->run();

    }

}
