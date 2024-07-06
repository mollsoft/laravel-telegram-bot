<?php

namespace Mollsoft\Telegram;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Mollsoft\Telegram\Commands\NewBotCommand;
use Mollsoft\Telegram\Commands\PoolingCommand;
use Mollsoft\Telegram\Commands\SetWebhookCommand;
use Mollsoft\Telegram\Commands\UnsetWebhookCommand;
use Mollsoft\Telegram\Providers\RouteServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TelegramServiceProvider extends PackageServiceProvider
{
    public function boot(): static
    {
        $this->app->register(RouteServiceProvider::class);

        return parent::boot();
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('telegram')
            ->hasConfigFile('telegram')
            ->hasViews('telegram')
            ->hasRoutes('api')
            ->hasMigrations([
                'create_telegram_bots_table',
                'create_telegram_chats_table',
            ])
            ->hasCommands([
                NewBotCommand::class,
                SetWebhookCommand::class,
                UnsetWebhookCommand::class,
                PoolingCommand::class,
            ])
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations();
            });

        $this->loadViewsFrom(resource_path('views/telegram'), 'telegram');

        $this->app->register(RouteServiceProvider::class);
    }
}
