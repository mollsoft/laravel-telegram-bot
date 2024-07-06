<?php

namespace Mollsoft\Telegram\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Mollsoft\Telegram\Models\TelegramBot;
use Mollsoft\Telegram\Services\WebhookHandler;

class WebhookController
{
    public function handle(Request $request, string $token, WebhookHandler $handler): Response
    {
        try {
            $bot = TelegramBot::whereToken($token)->firstOrFail();

            $handler->handle($request, $bot);
        } catch (\Exception $e) {
            Log::error($e);
        }

        return response()->noContent();
    }
}
