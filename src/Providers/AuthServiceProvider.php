<?php

declare(strict_types=1);

namespace Mollsoft\Telegram\Providers;


use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Mollsoft\Telegram\Middleware\Authenticate;
use Mollsoft\Telegram\Middleware\RedirectIfAuthenticated;
use Mollsoft\Telegram\TelegramGuard;
use Mollsoft\Telegram\TelegramRequest;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(Router $router): void
    {
        $router->aliasMiddleware('auth', Authenticate::class);
        $router->aliasMiddleware('guest', RedirectIfAuthenticated::class);

        Auth::extend('telegram', function (Application $app, string $name, array $config) {
            return new TelegramGuard(Auth::createUserProvider($config['provider']), $app->get(TelegramRequest::class));
        });
    }
}
