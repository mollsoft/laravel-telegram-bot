<?php

namespace Mollsoft\Telegram;

use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\Foundation\RedisCollection;
use Mollsoft\Telegram\Models\TelegramChat;

/**
 * @extends RedisCollection<Message>
 */
class MessageStack extends RedisCollection
{
    public function __construct(TelegramChat $chat)
    {
        parent::__construct(
            redisKey: get_class($chat).'::'.$chat->getKey(),
            getter: fn (mixed $value) => Message::fromArray(json_decode($value, true)),
            setter: fn (Message $value) => json_encode($value->toArray()),
        );
    }
}
