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

    public function requestContact(): bool
    {
        return !!$this->get('request_contact', false);
    }

    public function setRequestContact(bool $requestContact): static
    {
        $this->attributes['request_contact'] = $requestContact;

        return $this;
    }

    public function requestLocation(): bool
    {
        return !!$this->get('request_location', false);
    }

    public function setRequestLocation(bool $requestLocation): static
    {
        $this->attributes['request_location'] = $requestLocation;

        return $this;
    }

    public function webApp(): ?array
    {
        return $this->attributes['web_app'] ?? null;
    }

    public function setWebApp(?array $webApp): static
    {
        $this->attributes['web_app'] = $webApp;

        return $this;
    }
}
