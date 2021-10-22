<?php

namespace App\Traits\Old;

trait TinkoffControllerOld
{

    public function LoadAllTickers()
    {

        return $this->tinkoff->LoadAllTickers();

    }

}
