<?php

declare(strict_types=1);

namespace Mollsoft\Telegram\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Mollsoft\Telegram\Middleware\TelegramMiddleware;

class RouteServiceProvider extends ServiceProvider
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

        parent::boot();
    }

    public function map(): void
    {
        Route::middlewareGroup('telegram', [
            TelegramMiddleware::class,
        ]);

        if( is_file(base_path('routes/telegram.php')) ) {
            Route::middleware(['telegram', ...config('telegram.middleware', [])])
                ->name('telegram.')
                ->group(base_path('routes/telegram.php'));
        }
    }
}
