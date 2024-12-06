<?php

namespace Mollsoft\Telegram\Services;

use Illuminate\Support\Facades\Date;
use Mollsoft\Telegram\Jobs\TelegramLiveJob;
use Mollsoft\Telegram\Models\TelegramChat;

class LiveRunService
{
    protected ?\Closure $logger = null;
    protected float $startedAt;

    public function __construct()
    {
        $this->startedAt = microtime(true);
    }

    public function setLogger(\Closure $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    public function log(string $message, string $type = 'info'): static
    {
        if ($this->logger) {
            call_user_func($this->logger, '['.round((microtime(true) - $this->startedAt), 4).' s] '.$message, $type);
        }

        return $this;
    }

    public function run(): void
    {
        $this->log('Started...');

        TelegramChat::query()
            ->whereNotNull('live_expire_at')
            ->where('live_expire_at', '<=', Date::now())
            ->each(function (TelegramChat $chat) {
                $chat->update([
                    'live_period' => null,
                    'live_launch_at' => null,
                    'live_expire_at' => null,
                ]);

                $this->log("Live mode for Telegram Chat $chat->id is expired.");
            });

        TelegramChat::query()
            ->whereNotNull('live_launch_at')
            ->where('live_launch_at', '<=', Date::now())
            ->each(function (TelegramChat $chat) {
                $chat->update([
                    'live_launch_at' => Date::now()->addSeconds($chat->live_period),
                ]);

                TelegramLiveJob::dispatch($chat);

                $this->log('Job successfully dispatched!', 'success');
            });

        $this->log('Finished!');
    }
}
