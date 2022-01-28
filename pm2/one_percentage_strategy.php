<?php

use App\Hiney\Strategies\OnePercentage;

require_once dirname(__DIR__) . '/public/index.php';

$condition = isset($argv[1]) && isset($argv[2]) && isset($argv[3]);

if (!$condition) die('Give right arguments!' . PHP_EOL);

$pair = $argv[1];
$profit = $argv[2];
$change_price = $argv[3];

while (true) {

    (new OnePercentage($pair, $profit, $change_price))->run();

}
