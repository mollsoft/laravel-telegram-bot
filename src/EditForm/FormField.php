<?php

namespace Mollsoft\Telegram\EditForm;

class FormField
{
    public function __construct(
        public readonly string $name,
        public readonly string $title,
        public readonly bool $optional,
        public ?string $default,
        public ?string $value,
        public ?string $error,
    ) {
    }
}
