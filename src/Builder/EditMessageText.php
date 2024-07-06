<?php

namespace Mollsoft\Telegram\Builder;

use Mollsoft\Telegram\Abstract\ApiClient;
use Mollsoft\Telegram\DTO\InlineKeyboard;
use Mollsoft\Telegram\DTO\Message;

class EditMessageText
{
    protected ?InlineKeyboard $inlineKeyboard = null;

    public function __construct(
        protected readonly ApiClient $api,
        protected readonly string|int $chatId,
        protected readonly int $messageId,
        protected string $text
    ) {
    }

    public function send(): Message
    {
        $data = [
            'chat_id' => $this->chatId,
            'message_id' => $this->messageId,
            'text' => $this->text,
        ];

        if ($this->inlineKeyboard) {
            $data['reply_markup'] = [
                'inline_keyboard' => $this->inlineKeyboard->toArray(),
            ];
        }

        $result = $this->api->sendRequest('editMessageText', $data);

        return Message::fromArray($result);
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function inlineKeyboard(?InlineKeyboard $inlineKeyboard): static
    {
        $this->inlineKeyboard = $inlineKeyboard;

        return $this;
    }
}
