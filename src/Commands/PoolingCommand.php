<?php

namespace Mollsoft\Telegram\Commands;

use Illuminate\Console\Command;
use Mollsoft\Telegram\Services\PollingService;

class PoolingCommand extends Command
{
    protected $signature = 'telegram:pooling {bot}';

    protected $description = 'Command description';

    public function handle(PollingService $service): void
    {
        $service
            ->setLogger(
                fn(
                    string $message,
                    string $type
                ) => $this->{$type === 'info' ? 'line' : ($type === 'success' ? 'info' : $type)}($message)
            )
            ->run(
                $this->argument('bot')
            );
    }
}
