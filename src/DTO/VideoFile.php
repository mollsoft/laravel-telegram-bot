<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;
use Mollsoft\Telegram\Interfaces\IsFile;

class VideoFile extends DTO implements IsFile
{
    protected function required(): array
    {
        return ['file_id', 'file_unique_id', 'width', 'height', 'duration'];
    }

    public function fileId(): string
    {
        return $this->getOrFail('file_id');
    }

    public function fileUniqueId(): string
    {
        return $this->getOrFail('file_unique_id');
    }

    public function width(): int
    {
        return (int)$this->getOrFail('width');
    }

    public function height(): int
    {
        return (int)$this->getOrFail('height');
    }

    public function duration(): int
    {
        return (int)$this->getOrFail('duration');
    }

    public function thumbnail(): ?PhotoSize
    {
        $value = $this->get('thumbnail');

        return $value !== null ? PhotoSize::fromArray($value) : null;
    }

    public function fileName(): ?string
    {
        return $this->get('file_name');
    }

    public function mimeType(): ?string
    {
        return $this->get('mime_type');
    }

    public function fileSize(): ?int
    {
        $fileSize = $this->get('file_size');

        return $fileSize !== null ? (int)$fileSize : null;
    }
}
