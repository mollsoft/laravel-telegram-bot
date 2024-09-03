<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;
use Mollsoft\Telegram\Interfaces\IsFile;

class VoiceNote extends DTO implements IsFile
{
    protected function required(): array
    {
        return ['file_id', 'file_unique_id', 'duration', 'mime_type', 'file_size'];
    }

    public function fileId(): string
    {
        return $this->getOrFail('file_id');
    }

    public function fileUniqueId(): string
    {
        return $this->getOrFail('file_unique_id');
    }

    public function duration(): int
    {
        return (int)$this->getOrFail('duration');
    }

    public function mimeType(): string
    {
        return (int)$this->getOrFail('mime_type');
    }

    public function fileSize(): ?int
    {
        $fileSize = $this->get('file_size');

        return $fileSize !== null ? (int)$fileSize : null;
    }
}
