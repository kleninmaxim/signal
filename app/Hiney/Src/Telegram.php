<?php

namespace App\Hiney\Src;

use App\Models\ErrorLog;
use Telegram\Bot\Api;
use Throwable;

class Telegram
{
    private Api $telegram;
    private int|float $chat_id;

    public function __construct()
    {

        try {

            $this->telegram = new Api(config('api.telegram_token_binance'));

            $this->chat_id = config('api.telegram_user_id');

        } catch (Throwable $e) {

            ErrorLog::create([
                'title' => 'Telegram not created throw api key',
                'message' => json_encode($e),
            ]);

        }

    }

    public function send($message): bool
    {

        try {

            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $message]);

        } catch (Throwable $e) {

            ErrorLog::create([
                'title' => 'Telegram not send message',
                'message' => json_encode($e),
            ]);

            return false;

        }

        return true;

    }

}
