<?php

namespace Mollsoft\Telegram\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mollsoft\Telegram\Models\TelegramChat;
use Mollsoft\Telegram\Services\LiveJobService;

class TelegramLiveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected TelegramChat $chat;

    public function __construct(TelegramChat $chat)
    {
        $this->chat = $chat;

        $this->onQueue('telegram');
    }

    public function uniqueId(): string
    {
        return __CLASS__.'::'.$this->chat->id;
    }

    public function handle(LiveJobService $service): void
    {
        $service->run($this->chat);
    }
}
