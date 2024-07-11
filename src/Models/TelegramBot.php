<?php

namespace Mollsoft\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Mollsoft\Telegram\API;

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

    public function api(): API
    {
        return new API($this->token);
    }
}
