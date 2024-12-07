<?php

use Illuminate\Support\Facades\Route;
use Mollsoft\Telegram\Controllers\WebhookController;

Route::post('telegram/live', [WebhookController::class, 'live'])
    ->name('telegram.live');

Route::post('telegram/{token}/webhook', [WebhookController::class, 'handle'])
    ->name('telegram.webhook');

Route::post('telegram/{token}/execute', [WebhookController::class, 'execute'])
    ->name('telegram.execute');
