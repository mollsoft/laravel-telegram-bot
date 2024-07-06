<?php

namespace Mollsoft\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramChat extends Model
{
    protected $fillable = [
        'bot_id',
        'chat_id',
        'username',
        'first_name',
        'last_name',
        'chat_data',
    ];

    protected function casts(): array
    {
        return [
            'chat_data' => 'json',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'bot_id');
    }
}
