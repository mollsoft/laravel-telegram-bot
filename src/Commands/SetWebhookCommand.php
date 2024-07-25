<?php

namespace Mollsoft\Telegram\Commands;

use Illuminate\Console\Command;
use Mollsoft\Telegram\API;
use Mollsoft\Telegram\Facades\Telegram;
use Mollsoft\Telegram\Models\TelegramBot;

class SetWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook';

    protected $description = 'Set Webhook for Telegram Bot';

    public function handle(): void
    {
        /** @var class-string<TelegramBot> $model */
        $model = Telegram::botModel();

        $telegramBots = $model::get();
        if ($telegramBots->count() === 0) {
            $this->error('First register the Telegram bot using the command: php artisan telegram:new-bot');
            return;
        }

        $username = $this->choice('Which telegram bot do you want to set?', $telegramBots->pluck('username')->all());

        $telegramBot = $telegramBots->where('username', $username)->firstOrFail();

        try {
            $url = route('telegram.webhook', [
                'token' => $telegramBot->token,
            ]);

            Telegram::setWebhook($telegramBot, $url);

            $this->info("Webhook successfully set for Telegram Bot @{$telegramBot->username}!");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
