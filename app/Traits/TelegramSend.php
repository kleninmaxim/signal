<?php

namespace App\Traits;

use App\Src\Telegram;

trait TelegramSend
{

    public function sendTelegramMessage($message)
    {

        return (new Telegram($this->telegram_token, $this->telegram_user_id))->send($message);

    }

}
