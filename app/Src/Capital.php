<?php

namespace App\Src;

use App\Traits\Capital\ComplexCapital;
use App\Traits\Capital\SimpleCapital;
use App\Traits\Strategy\FiveMinuteVolume;

class Capital
{

    use SimpleCapital, ComplexCapital, FiveMinuteVolume;

}
