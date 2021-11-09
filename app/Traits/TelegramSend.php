<?php

namespace App\Traits;

use App\Src\Telegram;

trait TelegramSend
{

    public function sendTelegramMessage($message, $user_id = '')
    {

        return (new Telegram(
            $this->telegram_token,
            empty($user_id) ? $this->telegram_user_id : $user_id)
        )->send($message);

    }

}
