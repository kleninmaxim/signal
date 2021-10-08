<?php

namespace App\Traits;

use App\Src\Telegram;

trait TelegramSend
{

    private function sendTelegramMessage($message)
    {

        return (new Telegram($this->telegram_token, $this->chat_id))->send($message);

    }

}
