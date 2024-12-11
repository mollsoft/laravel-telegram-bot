<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;
use Mollsoft\Telegram\Interfaces\IsFile;

class VideoNoteFile extends DTO implements IsFile
{
    protected function required(): array
    {
        return ['file_id', 'file_unique_id', 'length', 'duration'];
    }

    public function fileId(): string
    {
        return $this->getOrFail('file_id');
    }

    public function fileUniqueId(): string
    {
        return $this->getOrFail('file_unique_id');
    }

    public function length(): int
    {
        return (int)$this->getOrFail('length');
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

    public function fileSize(): ?int
    {
        $fileSize = $this->get('file_size');

        return $fileSize !== null ? (int)$fileSize : null;
    }
}
