<?php

use Illuminate\Support\Facades\Route;
use Mollsoft\Telegram\Controllers\WebhookController;

Route::post('telegram/{token}/webhook', [WebhookController::class, 'handle'])
    ->name('telegram.webhook');


