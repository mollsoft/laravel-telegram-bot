<?php

namespace Mollsoft\Telegram\Commands;

use Illuminate\Console\Command;
use Mollsoft\Telegram\API;
use Mollsoft\Telegram\Models\TelegramBot;

class NewBotCommand extends Command
{
    protected $signature = 'telegram:new-bot';

    protected $description = 'Register telegram bot in system';

    public function handle(): void
    {
        $this->start();
    }

    protected function start(): void
    {
        $token = $this->ask('Please enter telegram bot token');

        try {
            $api = new API($token);
            $getMe = $api->getMe();

            TelegramBot::updateOrCreate([
                'token' => $token,
            ], [
                'username' => $getMe->username(),
                'get_me' => $getMe->toArray(),
            ]);

            $this->info("Telegram Bot @{$getMe->username()} successfully added!");
        }
        catch(\Exception $e) {
            $this->error($e->getMessage());
            $this->start();
            return;
        }

    }
}
