<?php

namespace App\Hiney\Src;

use App\Models\ErrorLog;
use Telegram\Bot\Api;
use Throwable;

class Telegram
{
    private Api $telegram;
    private array $chat_ids;

    public function __construct($dima = false)
    {

        try {

            $this->telegram = new Api(
                $dima ? config('api.telegram_token_rocket') : config('api.telegram_token_binance')
            );

            $this->chat_ids =  $dima
                ? [config('api.telegram_user_id'), config('api.telegram_dima_id')]
                : [config('api.telegram_user_id')];

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

            foreach ($this->chat_ids as $chat_id)
                $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'Markdown']);

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
