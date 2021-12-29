<?php

namespace App\Hiney\Src;

use Telegram\Bot\Api;
use Throwable;

class Telegram
{
    private Api $telegram;
    private int|float $chat_id;

    public function __construct($api, $chat_id)
    {

        try {

            $this->telegram = new Api($api);

            $this->chat_id = $chat_id;

        } catch (Throwable $e) {

            error_log('Telegram not create. Error: ' . $e . '. API is: ***secret***');

        }

    }

    public function send($message): bool
    {

        try {

            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $message]);

        } catch (Throwable $e) {

            error_log('Telegram not send message. Error: ' . $e . '. Message is: ' . $message);

            return false;

        }

        return true;

    }

}
