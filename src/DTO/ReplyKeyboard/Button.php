<?php

namespace Mollsoft\Telegram\DTO\ReplyKeyboard;

use Mollsoft\Telegram\Abstract\DTO;

class Button extends DTO
{
    public function required(): array
    {
        return ['text'];
    }

    public function text(): string
    {
        return $this->getOrFail('text');
    }

    public function setText(string $text): static
    {
        $this->attributes['text'] = $text;

        return $this;
    }
}
