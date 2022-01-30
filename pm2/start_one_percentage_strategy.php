<?php

require_once dirname(__DIR__) . '/public/index.php';

$pairs = [
    ['pair' => 'ETHUSDT', 'profit' => 4, 'change_price' => 1],
    ['pair' => '1000SHIBUSDT', 'profit' => 1, 'change_price' => 1],
    //['pair' => 'PEOPLEUSDT', 'profit' => 1, 'change_price' => 2],
];

foreach ($pairs as $pair) {

    \App\Hiney\Src\Pm2::start(
        __DIR__ . '/one_percentage_strategy.php',
        '[One Percentage Strategy] ' . $pair['pair'],
        'Strategy',
        args: '"' . $pair['pair'] . '"' . ' ' . '"' . $pair['profit'] . '"' . ' ' . '"' . $pair['change_price'] . '"'
    );

    echo 'Pair: ' . $pair['pair'] . ' was started' . PHP_EOL;

}
