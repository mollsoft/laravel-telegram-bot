<?php

namespace Mollsoft\Telegram\Commands;

use Illuminate\Console\Command;
use Mollsoft\Telegram\API;
use Mollsoft\Telegram\Facades\Telegram;
use Mollsoft\Telegram\Models\TelegramBot;

class UnsetWebhookCommand extends Command
{
    protected $signature = 'telegram:unset-webhook';

    protected $description = 'Unset Webhook for Telegram Bot';

    public function handle(): void
    {
        $telegramBots = TelegramBot::get();
        if ($telegramBots->count() === 0) {
            $this->error('First register the Telegram bot using the command: php artisan telegram:new-bot');
            return;
        }

        $username = $this->choice('Which telegram bot do you want to unset?', $telegramBots->pluck('username')->all());

        $telegramBot = $telegramBots->where('username', $username)->firstOrFail();

        try {
            Telegram::setWebhook($telegramBot);

            $this->info("Webhook successfully unset for Telegram Bot @{$telegramBot->username}!");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
