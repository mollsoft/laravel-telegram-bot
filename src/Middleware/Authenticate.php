<?php

namespace Mollsoft\Telegram\Middleware;

use Illuminate\Http\Request;
use Mollsoft\Telegram\Foundation\TelegramRequest;

class Authenticate extends \Illuminate\Auth\Middleware\Authenticate
{
    protected function redirectTo(Request $request)
    {
        if ($request instanceof TelegramRequest) {
            return route('telegram.user.auth');
        }

        if (static::$redirectToCallback) {
            return call_user_func(static::$redirectToCallback, $request);
        }
    }
}
