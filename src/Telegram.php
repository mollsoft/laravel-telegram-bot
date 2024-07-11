<?php

namespace Mollsoft\Telegram;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Mollsoft\Telegram\Models\TelegramChat;
use Mollsoft\Telegram\Services\HTMLParser;
use Mollsoft\Telegram\Services\TelegramRender;

class Telegram
{
    public function sendMessage(TelegramChat $chat, View|string $html): array
    {
        if ($html instanceof View) {
            $html = $html->render();
        }

        try {
            $stack = new MessageStack($chat);
            $api = $chat->api();
            $parser = new HTMLParser($html);
            $render = new TelegramRender($api, $stack, $parser);
            return $render->run();
        } catch (\Exception $e) {
            Log::error($e);
        }

        return [];
    }
}
