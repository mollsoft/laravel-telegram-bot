<?php

namespace Mollsoft\Telegram\Middleware;

use Illuminate\Support\Facades\Route;

class RedirectIfAuthenticated extends \Illuminate\Auth\Middleware\RedirectIfAuthenticated
{
    protected function defaultRedirectUri(): string
    {
        foreach (['telegram.dashboard', 'telegram.home', 'telegram.index', 'dashboard', 'home', 'index'] as $uri) {
            if (Route::has($uri)) {
                return route($uri);
            }
        }

        $routes = Route::getRoutes()->get('GET');

        foreach (['telegram.dashboard', 'telegram.home', 'telegram.index', 'dashboard', 'home', 'index'] as $uri) {
            if (isset($routes[$uri])) {
                return '/'.$uri;
            }
        }

        return '/';
    }
}
