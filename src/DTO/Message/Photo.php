<?php

namespace Mollsoft\Telegram\DTO\Message;


use Illuminate\Support\Collection;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\DTO\PhotoSize;
use Mollsoft\Telegram\Interfaces\HasCaption;

class Photo extends Message implements HasCaption
{
    /**
     * @return ?Collection<PhotoSize>
     */
    public function photo(): ?Collection
    {
        $value = $this->get('photo');

        return is_array($value) ? collect(array_map(fn($item) => PhotoSize::fromArray($item), $value)) : null;
    }

    public function photoSrc(): ?string
    {
        return $this->get('photo_src');
    }

    public function setPhotoSrc(string $path): static
    {
        $this->attributes['photo_src'] = $path;

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
            'photo_src' => $this->get('photo_src'),
            'caption' => $this->captionSignature(),
            'reply_markup' => $this->get('reply_markup'),
        ]));
    }
}
