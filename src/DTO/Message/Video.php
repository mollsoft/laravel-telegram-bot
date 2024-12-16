<?php

namespace Mollsoft\Telegram\DTO\Message;


use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\DTO\VideoFile;
use Mollsoft\Telegram\Interfaces\HasCaption;

class Video extends Message implements HasCaption
{
    public function video(): ?VideoFile
    {
        $value = $this->get('video');

        return $value ? VideoFile::fromArray($value) : null;
    }

    public function videoSrc(): ?string
    {
        return $this->get('video_src');
    }

    public function setVideoSrc(string $path): static
    {
        $this->attributes['video_src'] = $path;

        return $this;
    }

    public function caption(): ?string
    {
        return $this->get('caption');
    }

    public function showCaptionAboveMedia(): ?bool
    {
        $value = $this->get('show_caption_above_media');
        return $value !== null ? (bool)$value : null;
    }

    public function setShowCaptionAboveMedia(?bool $value): static
    {
        $this->attributes['show_caption_above_media'] = $value;

        return $this;
    }

    public function captionEntities(): ?array
    {
        return $this->get('caption_entities');
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
            'video_src' => $this->get('video_src'),
            'caption' => $this->captionSignature(),
            'reply_markup' => $this->get('reply_markup'),
        ]));
    }
}
