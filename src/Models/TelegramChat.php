<?php

namespace Mollsoft\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mollsoft\Telegram\ChatAPI;
use Mollsoft\Telegram\Facades\Telegram;

class TelegramChat extends Model
{
    protected $fillable = [
        'bot_id',
        'chat_id',
        'username',
        'first_name',
        'last_name',
        'chat_data',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'chat_data' => 'json',
        ];
    }

    public function bot(): BelongsTo
    {
        /** @phpstan-ignore-next-line */
        return $this->belongsTo(Telegram::botModel(), 'bot_id');
    }

    public function api(): ChatAPI
    {
        return new ChatAPI($this->bot->token, $this->chat_id);
    }
}
