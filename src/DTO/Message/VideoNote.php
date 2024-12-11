<?php

namespace Mollsoft\Telegram\DTO\Message;


use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\DTO\VideoNoteFile;

class VideoNote extends Message
{
    public function videoNote(): ?VideoNoteFile
    {
        $value = $this->get('video_note');

        return $value ? VideoNoteFile::fromArray($value) : null;
    }

    public function videoNoteSrc(): ?string
    {
        return $this->get('video_note_src');
    }

    public function setVideoNoteSrc(string $path): static
    {
        $this->attributes['video_note_src'] = $path;

        return $this;
    }

    public function signature(): string
    {
        return hash('sha256', json_encode([
            'video_note_src' => $this->get('video_note_src'),
            'reply_markup' => $this->get('reply_markup'),
        ]));
    }
}
