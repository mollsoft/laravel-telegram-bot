<?php

namespace Mollsoft\Telegram\Exceptions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Throwable;

class RedirectException extends \Exception
{
    public readonly RedirectResponse $redirect;

    public function __construct(RedirectResponse|string|array $redirect, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        if( is_array($redirect) ) {
            $this->redirect = to_route(Route::currentRouteName(), [
                ...request()->route()->parameters(),
                ...request()->query(),
                ...$redirect
            ]);
        }
        else {
            $this->redirect = $redirect instanceof RedirectResponse ? $redirect : redirect()->to($redirect);
        }
    }

    public function render(): mixed
    {
        return $this->redirect;
    }
}
