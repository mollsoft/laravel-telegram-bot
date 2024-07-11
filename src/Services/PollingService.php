<?php

namespace Mollsoft\Telegram\Services;

use Illuminate\Support\Facades\Process;
use Mollsoft\Telegram\API;
use Mollsoft\Telegram\DTO\Update;
use Mollsoft\Telegram\Foundation\BaseService;
use Mollsoft\Telegram\Models\TelegramBot;

class PollingService extends BaseService
{
    protected ?TelegramBot $bot;
    protected ?API $api;
    protected ?string $webhookURL = null;

    protected function init(mixed $botId): static
    {
        /** @var TelegramBot $botClass */
        $this->bot = TelegramBot::findOrFail($botId);
        $this->api = new API($this->bot->token);
        $this->webhookURL = route('telegram.execute', [
            'token' => $this->bot->token
        ]);

        return $this;
    }

    protected function botInit(): static
    {
        $commands = config('telegram.init.default.commands');
        if (count($commands) === 0) {
            $this->api->deleteMyCommands();
        } else {
            $this->api->setMyCommands($commands);
        }

        return $this;
    }

    public function run(mixed $botId): void
    {
        try {
            $this
                ->init($botId)
                ->botInit();

            $this->log("Started polling for Telegram Bot @{$this->bot->username}");

            $offset = null;
            do {
                $updates = [];

                $this->log('Wait updates...');

                try {
                    $updates = $this->api->getUpdates(
                        offset: $offset === null ? null : $offset + 1,
                        limit: 10,
                        timeout: 15
                    );
                } catch (\Exception $e) {
                    $this->error('Get Updates Exception: '.$e->getMessage());
                    sleep(10);
                }

                if (count($updates)) {
                    $this->log('Received '.count($updates).' updates');
                }

                /** @var Update $update */
                foreach ($updates as $update) {
                    $offset = $offset === null ? $update->id() : max($offset, $update->id());

                    try {
                        $postData = [
                            'update_id' => $update->id(),
                        ];
                        if ($update->message()) {
                            $postData['message'] = $update->message()->toArray();
                        }
                        if ($update->callbackQuery()) {
                            $postData['callback_query'] = $update->callbackQuery()->toArray();
                        }

                        if (isset($postData['message']['id'])) {
                            $postData['message']['message_id'] = $postData['message']['id'];
                        }
                        if (isset($postData['callback_query']['message']['id'])) {
                            $postData['callback_query']['message']['message_id'] = $postData['callback_query']['message']['id'];
                        }
                        if (is_array($postData['callback_query']['data'] ?? null)) {
                            $postData['callback_query']['data'] = collect($postData['callback_query']['data'])
                                ->map(fn($value, $key) => "{$key}:{$value}")
                                ->implode(';');
                        }

                        $cmd = 'curl -X POST '.$this->webhookURL.' -d "'.http_build_query($postData).'" > /dev/null &';
                        Process::run($cmd);
                    } catch (\Exception $e) {
                        $this->error('Webhook Handler Exception '.get_class($e).': '.$e->getMessage());
                    }
                }
            } while (true);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
