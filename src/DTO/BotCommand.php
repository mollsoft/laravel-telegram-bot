<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;

class BotCommand extends DTO
{
    protected function required(): array
    {
        return ['command', 'description'];
    }

    public function command(): string
    {
        return $this->getOrFail('command');
    }

    public function description(): string
    {
        return $this->getOrFail('description');
    }

    public static function create(string $command, string $description): static
    {
        return new static(
            compact('command', 'description')
        );
    }
}
