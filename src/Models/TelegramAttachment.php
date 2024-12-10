<?php

namespace Mollsoft\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mollsoft\Telegram\DTO\Document;
use Mollsoft\Telegram\DTO\PhotoSize;
use Mollsoft\Telegram\Facades\Telegram;
use Mollsoft\Telegram\Interfaces\IsFile;

class TelegramAttachment extends Model
{
    protected $fillable = [
        'bot_id',
        'chat_id',
        'type',
        'caption',
        'data',
        'storage_disk',
        'file_path',
        'attachmentable_type',
        'attachmentable_id',
    ];

    protected $appends = [
        'dto'
    ];

    protected function casts(): array
    {
        return [
            'data' => 'json',
        ];
    }

    public function getDtoAttribute(): ?IsFile
    {
        switch ($this->type) {
            case 'photo':
                return PhotoSize::fromArray($this->data);

            case 'document':
                return Document::fromArray($this->data);
        }

        return null;
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Telegram::botModel(), 'bot_id');
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Telegram::chatModel(), 'chat_id', 'chat_id');
    }

    public function attachmentable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        static::deleting(function (self $attachment) {
            if ($attachment->storage_disk && $attachment->file_path) {
                Storage::disk($attachment->storage_disk)
                    ->delete($attachment->file_path);
            }
        });
    }

    public function storageSave(string $filePath, string $storageDisk = 'public'): bool
    {
        $file = $this->dto;
        if (!$file) {
            return false;
        }

        $downloadLink = $this->bot->api()->getFileLink($file);
        if( mb_substr($downloadLink, 0, 1) === '/' ) {
            if( !is_file( $downloadLink ) ) {
                return false;
            }

            $extension = pathinfo($downloadLink, PATHINFO_EXTENSION);
            $fileName = $filePath.'/'.md5_file($downloadLink).'.'.$extension;

            rename($downloadLink, Storage::disk($storageDisk)->path($fileName));
        }
        else {
            $fileContent = Http::get($downloadLink)->body();
            $extension = pathinfo(parse_url($downloadLink, PHP_URL_PATH), PATHINFO_EXTENSION);
            $fileName = $filePath.'/'.md5($fileContent).'.'.$extension;

            Storage::disk($storageDisk)->put($fileName, $fileContent);
        }

        $this->fill([
            'storage_disk' => $storageDisk,
            'file_path' => $fileName,
        ]);

        return true;
    }

    public function storageGet(): ?string
    {
        if (!$this->storage_disk || !$this->file_path) {
            return null;
        }

        return Storage::disk($this->storage_disk)
            ->path($this->file_path);
    }
}
