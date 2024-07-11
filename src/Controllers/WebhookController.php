<?php

namespace Mollsoft\Telegram\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Mollsoft\Telegram\Models\TelegramBot;
use Mollsoft\Telegram\Services\WebhookHandler;

class WebhookController
{
    public function handle(Request $request, string $token, WebhookHandler $handler): Response
    {
        $background = config('telegram.webhook.background', false);
        if ($background) {
            $executeURL = route('telegram.execute', compact('token'));
            $cmd = 'curl -X POST '.$executeURL.' -d "'.http_build_query($request->post()).'" > /dev/null &';
            Process::run($cmd);
        } else {
            $this->execute($request, $token, $handler);
        }

        return response()->noContent();
    }

    public function execute(Request $request, string $token, WebhookHandler $handler): Response
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
