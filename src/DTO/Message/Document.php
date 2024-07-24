<?php

namespace Mollsoft\Telegram\DTO\Message;


use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\Interfaces\HasCaption;

class Document extends Message implements HasCaption
{
    public function documentSrc(): ?string
    {
        return $this->get('document_src');
    }

    public function setDocumentSrc(string $path): static
    {
        $this->attributes['document_src'] = $path;

        return $this;
    }

    public function caption(): ?string
    {
        return $this->get('caption');
    }

    public function setCaption(?string $caption): static
    {
        $this->attributes['caption'] = $caption;

        return $this;
    }

    public function captionSignature(): ?string
    {
        $caption = $this->get('caption');
        $caption = strip_tags($caption);
        $caption = preg_replace('/\s+/', '', $caption);

        return hash('sha256', $caption);
    }

    public function signature(): string
    {
        return hash('sha256', json_encode([
            'document_src' => $this->get('document_src'),
            'caption' => $this->captionSignature(),
            'reply_markup' => $this->get('reply_markup'),
        ]));
    }
}
