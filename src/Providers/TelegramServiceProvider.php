<?php

declare(strict_types=1);

namespace Mollsoft\Telegram\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mollsoft\Telegram\View\Components\Column;
use Mollsoft\Telegram\View\Components\Keyboard;
use Mollsoft\Telegram\View\Components\Line;
use Mollsoft\Telegram\View\Components\Message;
use Mollsoft\Telegram\View\Components\ReplyKeyboard;
use Mollsoft\Telegram\View\Components\Row;
use Mollsoft\Telegram\View\Components\Screen;

class TelegramServiceProvider extends ServiceProvider
{
    public function boot(): void
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
    }
}
