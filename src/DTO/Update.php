<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;

class Update extends DTO
{
    protected function required(): array
    {
        return [
            'update_id',
        ];
    }

    public function id(): int
    {
        return (int)$this->getOrFail('update_id');
    }
    public function message(): ?Message
    {
        $message = $this->get('message');

        return $message ? Message::fromArray($message) : null;
    }

    public function channelPost(): ?Message
    {
        $channelPost = $this->get('channel_post');

        return $channelPost ? Message::fromArray($channelPost) : null;
    }

    public function callbackQuery(): ?CallbackQuery
    {
        $callbackQuery = $this->get('callback_query');

        return $callbackQuery ? CallbackQuery::fromArray($callbackQuery) : null;
    }
}
