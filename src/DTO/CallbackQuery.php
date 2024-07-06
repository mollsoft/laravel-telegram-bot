<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;

class CallbackQuery extends DTO
{
    public function required(): array
    {
        return [
            'id',
            'from',
        ];
    }

    public function id(): string
    {
        return (string)$this->getOrFail('id');
    }

    public function from(): User
    {
        return User::fromArray(
            $this->getOrFail('from')
        );
    }

    public function data(): ?string
    {
        return $this->get('data');
    }

    public function getData(string $key, mixed $default = null): mixed
    {
        parse_str($this->data(), $queryParams);

        return $queryParams[$key] ?? $default;
    }

    public function hasData(string $key): bool
    {
        parse_str($this->data(), $queryParams);

        return isset($queryParams[$key]);
    }

    public function setData(?string $data): static
    {
        $this->attributes['data'] = $data;

        return $this;
    }

    public function message(): ?Message
    {
        $message = $this->get('message');

        return $message ? Message::fromArray($message) : null;
    }
}
