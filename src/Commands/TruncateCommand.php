<?php

namespace Mollsoft\Telegram\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\Facades\Telegram;
use Mollsoft\Telegram\MessageStack;
use Mollsoft\Telegram\Models\TelegramBot;
use Mollsoft\Telegram\Models\TelegramChat;

class TruncateCommand extends Command
{
    protected $signature = 'telegram:truncate';

    protected $description = 'Truncate dialogs for telegram bots';

    public function handle(): void
    {
        $screenTruncate = (int)config('telegram.screen.truncate', 0);
        if ($screenTruncate > 0) {
            /** @var class-string<TelegramChat> $model */
            $model = Telegram::chatModel();

            $model::query()
                ->with('bot')
                ->where('updated_at', '<', Date::now()->subSeconds($screenTruncate))
                ->each(function (TelegramChat $chat) {
                    try {
                        if( $this->eachChat($chat) ) {
                            $this->info("Chat $chat->id successfully truncated!");
                        }
                    }
                    catch( \Exception $e ) {
                        $this->error("Chat $chat->id error: {$e->getMessage()}");
                    }
                });
        }
    }

    protected function eachChat(TelegramChat $chat): bool
    {
        $stack = new MessageStack($chat);
        if ($stack->count() > 0) {
            $mainMessage = $stack->last(fn(Message $item) => $item->replyKeyboard() !== null);

            $deleteMessages = $stack
                ->collect()
                ->map(fn(Message $item) => $item->id())
                ->filter(fn($id) => $id !== $mainMessage?->id())
                ->all();

            $chat->api()->deleteMessages($deleteMessages);
            $stack->truncate();

            if ($mainMessage) {
                $stack->push($mainMessage);
            }

            return true;
        }

        return false;
    }
}
