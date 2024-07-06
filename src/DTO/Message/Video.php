<?php

namespace Mollsoft\Telegram\DTO\Message;


use Mollsoft\Telegram\DTO\Message;

class Video extends Message
{
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
