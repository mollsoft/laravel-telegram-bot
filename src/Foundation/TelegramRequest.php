<?php

declare(strict_types=1);

namespace Mollsoft\Telegram\Foundation;

use Mollsoft\Telegram\ChatAPI;
use Mollsoft\Telegram\DTO\CallbackQuery;
use Mollsoft\Telegram\MessageStack;
use Mollsoft\Telegram\Models\TelegramBot;
use Mollsoft\Telegram\Models\TelegramChat;
use Mollsoft\Telegram\Storage;
use Symfony\Component\HttpFoundation\InputBag;

class TelegramRequest extends \Illuminate\Http\Request
{
    protected TelegramBot $bot;
    protected TelegramChat $chat;
    protected ChatAPI $api;
    protected Storage $storage;
    protected MessageStack $stack;
    protected ?string $text;
    protected ?CallbackQuery $callbackQuery;

    public static function createFromTelegram(
        TelegramBot $bot,
        TelegramChat $chat,
        string $uri,
        ?string $text = null,
        ?CallbackQuery $callbackQuery = null,
    ): static {
        return static::create(
            uri: $uri,
            method: 'TELEGRAM',
            parameters: $parameters ?? []
        )
            ->setBot($bot)
            ->setChat($chat)
            ->setText($text)
            ->setCallbackQuery($callbackQuery);
    }

    public function setBot(TelegramBot $bot): static
    {
        $this->bot = $bot;

        return $this;
    }

    public function setChat(TelegramChat $chat): static
    {
        $this->chat = $chat;
        $this->api = new ChatAPI($this->bot->token, $this->chat->chat_id);
        $this->storage = new Storage(get_class($chat).'_'.$chat->getKey());
        $this->stack = new MessageStack($chat);

        return $this;
    }

    public function bot(): TelegramBot
    {
        return $this->bot;
    }

    public function chat(): TelegramChat
    {
        return $this->chat;
    }

    public function api(): ChatAPI
    {
        return $this->api;
    }

    public function storage(): Storage
    {
        return $this->storage;
    }

    public function stack(): MessageStack
    {
        return $this->stack;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function callbackQuery(): ?CallbackQuery
    {
        return $this->callbackQuery;
    }

    public function setCallbackQuery(?CallbackQuery $callbackQuery): static
    {
        $this->callbackQuery = $callbackQuery;
        if( $callbackQuery ) {
            $this->request = new InputBag($callbackQuery->getAllData());
        }

        return $this;
    }

    public function hasText(): bool
    {
        return !empty($this->text);
    }

    public function text(): ?string
    {
        return $this->text;
    }
}
