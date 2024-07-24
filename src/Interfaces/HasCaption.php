<?php

namespace Mollsoft\Telegram\Interfaces;

interface HasCaption
{
    public function caption(): ?string;
    public function setCaption(?string $caption): static;
    public function captionSignature(): ?string;
}
