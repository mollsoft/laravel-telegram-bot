<?php

namespace Mollsoft\Telegram\Foundation;

abstract class BaseEntity
{
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }

    public static function fromArray(array $attributes): static
    {
        return new static($attributes);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
