<?php

namespace Mollsoft\Telegram\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\TelegramRequest;

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
        Auth::shouldUse('telegram');

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
            if( is_callable($callback = config('telegram.callback.back')) ) {
                call_user_func($callback, $request);
            }

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

                    case 'reload':
                        return $this->start($request, true);

                    case 'home':
                        return redirect('/');

                    case 'back':
                        if( is_callable($callback = config('telegram.callback.back')) ) {
                            call_user_func($callback, $request);
                        }
                        return redirect()->back();

                    case 'refresh':
                        $request->setMessage(null);
                        break;
                }
            }
        }

        if( ($encodeId = $request->callbackQuery()?->getData('encode')) && !Cache::get('telegram_'.$encodeId) ) {
            return $this->start($request, true);
        }

        return $next($request);
    }

    protected function start(TelegramRequest $request, bool $reload = false): mixed
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

        if( is_callable($callback = config('telegram.callback.start')) ) {
            call_user_func($callback, $request);
        }

        if( $reload ) {
            return redirect()->refresh();
        }

        return redirect('/');
    }
}
