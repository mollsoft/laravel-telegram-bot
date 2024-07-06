<?php

namespace Mollsoft\Telegram\Abstract;

abstract class DTO
{
    protected array $attributes;

    public function __construct(array $attributes = [], bool $validate = false)
    {
        $this->attributes = $attributes;

        if ($validate) {
            $this->validate();
        }
    }

    protected function validate(): void
    {
        foreach ($this->required() as $item) {
            if (!isset($this->attributes[$item])) {
                throw new \Exception('Attribute '.$item.' is required.');
            }
        }
    }

    protected function required(): array
    {
        return [];
    }

    protected function getOrFail(string $name): mixed
    {
        $keys = explode('.', $name);
        $value = $this->attributes;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                throw new \Exception('Attribute '.$name.' not found.');
            }
        }

        return $value;
    }

    protected function get(string $name, mixed $default = null): mixed
    {
        $keys = explode('.', $name);
        $value = $this->attributes;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public function toArray(): array
    {
        return $this->convertToArray($this->attributes);
    }

    private function convertToArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->convertToArray($value);
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                $data[$key] = $value->toArray();
            }
        }

        return $data;
    }

    public static function fromArray(array $attributes): static
    {
        return new static($attributes, true);
    }

    public static function make(array $attributes = [], bool $validate = false): static
    {
        return new static($attributes, $validate);
    }
}
