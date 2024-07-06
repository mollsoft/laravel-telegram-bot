<?php

namespace Mollsoft\Telegram\DTO;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Mollsoft\Telegram\Abstract\DTO;
use Mollsoft\Telegram\DTO\Message\Photo;
use Mollsoft\Telegram\DTO\Message\Video;
use Mollsoft\Telegram\Enums\Direction;

class Message extends DTO
{
    public static array $types = [
        'message' => Message::class,
        'photo' => Photo::class,
        'video' => Video::class,
    ];

    protected function required(): array
    {
        return [
            'message_id',
            'from',
            'date',
            'chat',
        ];
    }

    public function id(): int
    {
        return (int)$this->getOrFail('message_id');
    }

    public function from(): User
    {
        return User::fromArray(
            $this->getOrFail('from')
        );
    }

    public function direction(): Direction
    {
        $from = $this->get('from');
        if ($from) {
            $from = User::fromArray($from);
            if (!$from->isBot()) {
                return Direction::IN;
            }
        }

        return Direction::OUT;
    }

    public function date(): Carbon
    {
        return Date::createFromTimestampUTC(
            $this->getOrFail('date')
        );
    }

    public function chat(): Chat
    {
        return Chat::fromArray(
            $this->getOrFail('chat')
        );
    }

    public function text(): ?string
    {
        return $this->get('text');
    }

    public function setText(?string $text): static
    {
        $this->attributes['text'] = $text;

        return $this;
    }

    public function inlineKeyboard(): ?InlineKeyboard
    {
        return $this->get('reply_markup.inline_keyboard') ? InlineKeyboard::fromArray(
            $this->get('reply_markup')
        ) : null;
    }

    public function setInlineKeyboard(?InlineKeyboard $inlineKeyboard): static
    {
        $this->attributes['reply_markup'] = $inlineKeyboard->toArray();

        return $this;
    }

    public function replyKeyboard(): ?ReplyKeyboard
    {
        return $this->get('reply_markup.keyboard') ? ReplyKeyboard::fromArray(
            $this->get('reply_markup')
        ) : null;
    }

    public function setReplyKeyboard(?ReplyKeyboard $replyKeyboard): static
    {
        $this->attributes['reply_markup'] = $replyKeyboard->toArray();

        return $this;
    }

    public function replyMarkupSignature(): string
    {
        return hash('sha256', json_encode($this->get('reply_markup')));
    }

    public function signature(): string
    {
        return hash('sha256', json_encode([
            'text' => preg_replace('/\s+/', '', strip_tags($this->get('text'))),
            'reply_markup' => $this->get('reply_markup'),
        ]));
    }

    public static function fromArray(array $attributes): static
    {
        if (!isset($attributes['type'])) {
            $attributes['type'] = 'message';
        }
        if (isset($attributes['photo']) || isset($attributes['photo_src'])) {
            $attributes['type'] = 'photo';
        }
        if( isset( $attributes['video'] ) || isset($attributes['video_src']) ) {
            $attributes['type'] = 'video';
        }

        return new self::$types[$attributes['type']]($attributes, true);
    }
}
