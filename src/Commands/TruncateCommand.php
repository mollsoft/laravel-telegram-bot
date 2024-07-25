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

    protected int $screenTruncate;

    public function handle(): void
    {
        $this->screenTruncate = (int)config('telegram.screen.truncate', 0);

        if ($this->screenTruncate > 0) {
            /** @var class-string<TelegramChat> $model */
            $model = Telegram::chatModel();

            $model::query()
                ->with('bot')
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
                ->filter(fn(Message $item) => $item->id() !== $mainMessage?->id() && abs(Date::now()->diffInSeconds($item->date())) >= $this->screenTruncate )
                ->map(fn(Message $item) => $item->id())
                ->filter(fn($id) => $id !== $mainMessage?->id());

            $saveMessages = $stack->collect()
                ->filter(fn(Message $item) => $item->id() === $mainMessage?->id() || abs(Date::now()->diffInSeconds($item->date())) < $this->screenTruncate );

            if( $deleteMessages->count() ) {
                $chat->api()->deleteMessages($deleteMessages->all());
                $stack->truncate();

                foreach( $saveMessages as $message ) {
                    $stack->push($message);
                }

                return true;
            }
        }

        return false;
    }
}
