<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;
use Mollsoft\Telegram\DTO\ReplyKeyboard\Button;

class ReplyKeyboard extends DTO
{
    protected function required(): array
    {
        return ['keyboard'];
    }

    public function resize(): bool
    {
        return $this->get('resize_keyboard', false);
    }

    public function setResize(bool $resize): static
    {
        $this->attributes['resize_keyboard'] = $resize;

        return $this;
    }

    public function button(Button $button, ?int $rowIndex = null): static
    {
        if (!isset($this->attributes['keyboard'])) {
            $this->attributes['keyboard'] = [];
        }

        if (!is_null($rowIndex)) {
            if (!isset($this->attributes['keyboard'][$rowIndex])) {
                $this->attributes['keyboard'][$rowIndex] = [];
            }
            $this->attributes['keyboard'][$rowIndex][] = $button->toArray();
        } else {
            $this->attributes['keyboard'][] = [
                $button->toArray()
            ];
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return count($this->attributes['keyboard'] ?? []) === 0;
    }
}
