<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;
use Mollsoft\Telegram\Interfaces\IsFile;

class PhotoSize extends DTO implements IsFile
{
    protected function required(): array
    {
        return ['file_id', 'file_unique_id', 'width', 'height'];
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

    public function fileSize(): ?int
    {
        $fileSize = $this->get('file_size');

        return $fileSize !== null ? (int)$fileSize : null;
    }
}
