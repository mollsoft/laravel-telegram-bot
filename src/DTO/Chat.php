<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;

class Chat extends DTO
{
    protected function required(): array
    {
        return ['id'];
    }

    public function id(): int
    {
        return (int)$this->getOrFail('id');
    }

    public function username(): ?string
    {
        return $this->get('username');
    }

    public function firstName(): ?string
    {
        return $this->get('first_name');
    }

    public function lastName(): ?string
    {
        return $this->get('last_name');
    }
}
