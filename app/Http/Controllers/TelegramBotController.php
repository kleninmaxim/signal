<?php

namespace App\Http\TelegramBotController;

use Illuminate\Http\Request;
use Telegram\Bot\Api;

class TelegramBot extends TelegramBotController
{

    public function telegram()
    {

        $telegram = new Api(config('api.telegram_token'));

        $result = $telegram->getWebhookUpdates();

        $telegram->sendMessage([ 'chat_id' => $result["message"]["chat"]["id"], 'text' => $result["message"]["chat"]["id"] ]);

        return true;

    }

}
