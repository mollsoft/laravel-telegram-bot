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
        if ($redirect = $request->post('redirect')) {
            return redirect($redirect);
        }

        if ($request->post('start')) {
            return $this->start($request);
        }

        if ($request->post('home')) {
            return redirect('/');
        }

        if ($request->post('back')) {
            return redirect()->back();
        }

        if ($request->post('refresh')) {
            $request->setCallbackQuery(null);
        }

        if( $request->hasText() ) {
            foreach( config('telegram.reactions', [] ) as $key => $values ) {
                $has = false;
                foreach( $values as $value ) {
                    if( mb_strpos($request->text(), $value) === 0 ) {
                        $has = true;
                        break;
                    }
                }
                switch( $has ? $key : null ) {
                    case 'start':
                        return $this->start($request);

                    case 'home':
                        return redirect('/');

                    case 'back':
                        return redirect()->back();

                    case 'refresh':
                        $request->setText(null);
                        break;
                }
            }
        }

        return $next($request);
    }

    protected function start(TelegramRequest $request): mixed
    {
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
    }
}
