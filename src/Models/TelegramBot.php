<?php

namespace Mollsoft\Telegram\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramBot extends Model
{
    protected $fillable = [
        'token',
        'username',
        'get_me',
    ];

    protected function casts(): array
    {
        return [
            'get_me' => 'json',
        ];
    }
}
