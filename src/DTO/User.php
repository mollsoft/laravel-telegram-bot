<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;

class User extends DTO
{
    protected function required(): array
    {
        return [
            'id',
            'is_bot',
            'first_name',
        ];
    }

    public function id(): int
    {
        return intval(
            $this->getOrFail('id')
        );
    }

    public function isBot(): int
    {
        return boolval(
            $this->getOrFail('is_bot')
        );
    }

    public function firstName(): string
    {
        return $this->getOrFail('first_name');
    }

    public function lastName(): ?string
    {
        return $this->get('last_name');
    }

    public function username(): ?string
    {
        return $this->get('username');
    }

    public function isPremium(): bool
    {
        return !!$this->get('is_premium', false);
    }
}
