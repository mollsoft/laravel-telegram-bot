<?php

namespace Mollsoft\Telegram\Middleware;

use Closure;
use Mollsoft\Telegram\TelegramRequest;

class LiveMiddleware
{
    public function handle(TelegramRequest $request, Closure $next, mixed $period, mixed $timeout = 3600)
    {
        $period = intval($period);
        $timeout = intval($timeout);

        $request->setLive($period, $timeout);

        return $next($request);
    }
}
