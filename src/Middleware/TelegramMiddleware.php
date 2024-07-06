<?php

namespace Mollsoft\Telegram\Middleware;

use Closure;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\Foundation\TelegramRequest;

class TelegramMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  TelegramRequest  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(TelegramRequest $request, Closure $next): mixed
    {
        switch( $request->text() ) {
            case '/start':
                $stack = $request->stack();
                $stackMessages = $stack->collect();
                $stack->truncate();
                if (($last = $stackMessages->pop())) {
                    $stack->unshift($last);
                }

                $ids = $stackMessages->map(fn(Message $item) => $item->id());
                if ($ids->isNotEmpty()) {
                    $request->api()->try('deleteMessages', $ids->all());
                }

                $request->storage()->forget('uri');
                $request->storage()->forget('history');

                return redirect('/');

            case '/back':
                return redirect()->back();

            case '/refresh':
                $request->setText(null);
                break;
        }

        return $next($request);
    }
}
