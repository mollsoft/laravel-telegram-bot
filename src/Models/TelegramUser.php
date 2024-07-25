<?php

namespace Mollsoft\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Mollsoft\Telegram\Facades\Telegram;

class TelegramUser extends Model
{
    protected $fillable = [
        'telegram_chat_id',
        'authenticatable_type',
        'authenticatable_id',
    ];

    public function chats(): HasMany
    {
        /** @phpstan-ignore-next-line */
        return $this->hasMany(Telegram::chatModel(), 'chat_id', 'telegram_chat_id');
    }

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }
}
