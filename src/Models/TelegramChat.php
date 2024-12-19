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
        'visits',
        'live_period',
        'live_launch_at',
        'live_expire_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'chat_data' => 'json',
            'visits' => 'collection',
            'live_period' => 'integer',
            'live_launch_at' => 'datetime',
            'live_expire_at' => 'datetime',
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
