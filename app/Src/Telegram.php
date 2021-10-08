<?php

namespace App\Src;
use Telegram\Bot\Api;

class Telegram
{
    private $telegram;
    private $chat_id;

    public function __construct($api, $chat_id)
    {

        $this->telegram = new Api($api);

        $this->chat_id = $chat_id;

    }

    public function send($message)
    {

        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $message]);

        return true;

    }

}
