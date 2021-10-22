<?php

namespace App\Traits\Old;

trait TinkoffOld
{

    public function LoadAllTickers()
    {

        $stockes = $this->client->getStocks();

        foreach ($stockes as $stock) {

            $ticker = $stock->getTicker();

            echo $ticker . PHP_EOL;

            var_dump($this->addNewTicker($ticker));

        }

        return true;

    }

}
