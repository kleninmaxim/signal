<?php

namespace App\Http\Controllers\Kernels;

use App\Hiney\Strategies\OnePercentage;
use App\Http\Controllers\Controller;

class ShibOnePercentageController extends Controller
{

    public function shibOnePercentageStrategy()
    {

        (new OnePercentage('1000SHIBUSDT', 1, 1))->run();

    }

}
