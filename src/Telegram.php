<?php

namespace Mollsoft\Telegram;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Mollsoft\Telegram\Models\TelegramAttachment;
use Mollsoft\Telegram\Models\TelegramBot;
use Mollsoft\Telegram\Models\TelegramChat;
use Mollsoft\Telegram\Models\TelegramUser;
use Mollsoft\Telegram\Services\HTMLParser;
use Mollsoft\Telegram\Services\TelegramRender;

class Telegram
{
    /**
     * @return class-string<TelegramBot>
     */
    public function botModel(): string
    {
        return config('telegram.models.bot');
    }

    /**
     * @return class-string<TelegramBot>
     */
    public function chatModel(): string
    {
        return config('telegram.models.chat');
    }

    /**
     * @return class-string<TelegramUser>
     */
    public function userModel(): string
    {
        return config('telegram.models.user');
    }

    /**
     * @return class-string<TelegramAttachment>
     */
    public function attachmentModel(): string
    {
        return config('telegram.models.attachment');
    }

    public function newBot(string $token): TelegramBot
    {
        $api = new API($token);
        $getMe = $api->getMe();

        /** @var class-string<TelegramBot> $model */
        $model = $this->botModel();

        return $model::updateOrCreate([
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
            $chat->update([
                'updated_at' => Date::now(),
            ]);

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
