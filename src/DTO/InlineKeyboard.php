<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;
use Mollsoft\Telegram\DTO\InlineKeyboard\Button;

class InlineKeyboard extends DTO
{
    protected function required(): array
    {
        return ['inline_keyboard'];
    }

    public function button(Button $button, ?int $rowIndex = null): static
    {
        if (!isset($this->attributes['inline_keyboard'])) {
            $this->attributes['inline_keyboard'] = [];
        }

        if (!is_null($rowIndex)) {
            if (!isset($this->attributes['inline_keyboard'][$rowIndex])) {
                $this->attributes['inline_keyboard'][$rowIndex] = [];
            }
            $this->attributes['inline_keyboard'][$rowIndex][] = $button->toArray();
        } else {
            $this->attributes['inline_keyboard'][] = [
                $button->toArray()
            ];
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return count($this->attributes['inline_keyboard'] ?? []) === 0;
    }
}
