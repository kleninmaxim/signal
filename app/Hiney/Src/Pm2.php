<?php

namespace App\Hiney\Src;

class Pm2
{

    public static function start(
        string $script,
        string $name = null,
        string $namespace = null,
        string $output_log = "/dev/null",
        string $error_log = "/dev/null",
        string $args = null
    ): bool
    {
        $name = (is_null($name)) ? ' ' : ' --name "' . $name . '" ';
        $namespace = (is_null($namespace)) ? ' ' : ' --namespace "' . $namespace . '" ';
        $args = (is_null($args)) ? ' ' : ' -- ' . $args . ' ';

        $command = 'pm2 start ' . $script .
            $name .
            $namespace .
            '-o "' . $output_log . '" ' .
            '-e "' . $error_log . '" ' .
            '-m ' .
            '-f' .
            $args;

        exec($command, $result, $code);

        if ($code === 0) {
            echo "[OK] \"$script\" process started" . PHP_EOL;
            return true;
        } else {
            echo "[ERROR] Failed to start \"$script\" process" . PHP_EOL;
            return false;
        }
    }

}
