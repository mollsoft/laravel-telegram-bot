<?php

namespace Mollsoft\Telegram;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Mollsoft\Telegram\Models\TelegramBot;
use Mollsoft\Telegram\Models\TelegramChat;
use Mollsoft\Telegram\Services\HTMLParser;
use Mollsoft\Telegram\Services\TelegramRender;

class Telegram
{
    public function newBot(string $token): TelegramBot
    {
        $api = new API($token);
        $getMe = $api->getMe();

        return TelegramBot::updateOrCreate([
            'token' => $token,
        ], [
            'username' => $getMe->username(),
            'get_me' => $getMe->toArray(),
        ]);
    }

    public function setWebhook(TelegramBot $bot, ?string $webhookURL = null, array $options = []): void
    {
        $api = $bot->api();

        if ($webhookURL) {
            $api->setWebhook($webhookURL, $options);
        } else {
            $api->deleteWebhook($options);
        }
    }

    public function init(TelegramBot $bot, string $init = 'default'): void
    {
        $api = $bot->api();

        $config = config('telegram.init.'.$init, []);
        foreach ($config as $key => $value) {
            echo $key."\n";
            switch ($key) {
                case 'commands':
                    $api->setMyCommands($value);
                    break;

                case 'name':
                    $api->setMyName($value);
                    break;

                case 'description':
                    $api->setMyDescription($value);
                    break;

                case 'short_description':
                    $api->setMyShortDescription($value);
                    break;
            }
        }
    }

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
