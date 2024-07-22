<?php

declare(strict_types=1);

namespace Mollsoft\Telegram\Providers;


use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mollsoft\Telegram\Foundation\TelegramRequest;
use Mollsoft\Telegram\Middleware\Authenticate;
use Mollsoft\Telegram\Middleware\RedirectIfAuthenticated;
use Mollsoft\Telegram\TelegramGuard;

class TelegramServiceProvider extends ServiceProvider
{
    public function boot(Router $router): void
    {
        if (!Route::hasMacro('telegram')) {
            Route::macro('telegram', function ($url, $action) {
                /* @var Router $this */
                $router = $this->match(['TELEGRAM'], $url.'/{method?}', $action);
                $router->where('method', 'TELEGRAM');
                return $router;
            });
        }

        $this->loadViewsFrom(resource_path('views/telegram'), 'telegram');

        $this->app->register(RouteServiceProvider::class);

        $router->aliasMiddleware('auth', Authenticate::class);
        $router->aliasMiddleware('guest', RedirectIfAuthenticated::class);

        Auth::extend('telegram', function (Application $app, string $name, array $config) {
            return new TelegramGuard(Auth::createUserProvider($config['provider']), $app->get(TelegramRequest::class));
        });
    }
}
