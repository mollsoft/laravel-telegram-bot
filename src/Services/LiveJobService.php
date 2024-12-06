<?php

namespace Mollsoft\Telegram\Services;

use Illuminate\Support\Facades\Log;
use Mollsoft\Telegram\Models\TelegramChat;

class LiveJobService
{
    protected TelegramChat $chat;

    public function run(TelegramChat $chat): void
    {
        $this->chat = $chat;

        Log::error('TEST');
    }
}
