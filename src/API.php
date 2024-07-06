<?php

namespace Mollsoft\Telegram;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Mollsoft\Telegram\Abstract\ApiClient;
use Mollsoft\Telegram\Builder\SendMessage;
use Mollsoft\Telegram\DTO\BotCommand;
use Mollsoft\Telegram\DTO\Update;
use Mollsoft\Telegram\DTO\User;

class API extends ApiClient
{
    public function __construct(string $token)
    {
        $client = Http::baseUrl("https://api.telegram.org/bot$token/")
            ->connectTimeout(20)
            ->timeout(60);

        return parent::__construct($client);
    }

    /**
     * Возвращает информацию о боте.
     */
    public function getMe(): User
    {
        return User::fromArray(
            $this->sendRequest('getMe')
        );
    }

    /**
     * Установка WebHook для Updates.
     */
    public function setWebhook(string $url, array $options = []): bool
    {
        return $this->sendRequest('setWebhook', [
            'url' => $url,
            ...$options,
        ])[0];
    }

    /**
     * Удаление WebHook для Updates.
     */
    public function deleteWebhook(array $options = []): bool
    {
        return $this->sendRequest('deleteWebhook', $options)[0];
    }

    public function getUpdates(
        ?int $offset = null,
        ?int $limit = null,
        ?int $timeout = null,
        ?array $allowedUpdates = null
    ): array {
        $data = [];
        if (!is_null($offset)) {
            $data['offset'] = $offset;
        }
        if (!is_null($limit)) {
            $data['limit'] = $limit;
        }
        if (!is_null($timeout)) {
            $data['timeout'] = $timeout;
        }
        if (!is_null($allowedUpdates)) {
            $data['allowed_updates'] = $allowedUpdates;
        }

        $responseData = $this->sendRequest('getUpdates', $data);

        return array_map(fn(array $item) => Update::fromArray($item), $responseData);
    }

    public function sendMessage(string|int $chatId, string $text): SendMessage
    {
        return new SendMessage($this, $chatId, $text);
    }

    public function setMyCommands(BotCommand|array ...$commands): bool
    {
        /** @var BotCommand[] $commands */
        $commands = Arr::flatten($commands);
        $commands = Arr::map($commands, fn(BotCommand $item) => $item->toArray());

        return $this->sendRequest('setMyCommands', compact('commands'))[0];
    }

    public function deleteMyCommands(): bool
    {
        return $this->sendRequest('deleteMyCommands')[0];
    }
}
