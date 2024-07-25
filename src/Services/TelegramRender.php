<?php

namespace Mollsoft\Telegram\Services;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Mollsoft\Telegram\ChatAPI;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\MessageStack;

readonly class TelegramRender
{
    protected readonly int $screenTTL;

    public function __construct(
        protected ChatAPI $api,
        protected MessageStack $stack,
        protected HTMLParser $parser
    ) {
        $this->screenTTL = (int)config('telegram.screen.ttl', 86400);
    }

    public function run(): array
    {
        return match ($this->parser->type()) {
            'screen' => $this->screen(),
            'mixed' => $this->mixed(),
            'classic' => $this->classic(),
        };
    }

    protected function classic(): array
    {
        $array = [];

        foreach ($this->parser->appendMessages as $item) {
            $message = $this->api->send($item);
            $this->stack->push($message);

            $array[] = $message;
        }

        return $array;
    }

    protected function screen(): array
    {
        $array = [];

        $stackCursor = 0;
        $parserCursor = 0;

        $deleteMessages = [];
        $stackMessages = $this->stack->collect();
        $newMessages = $this->parser->screenMessages;

        while (true) {
            /** @var ?Message $stackMessage */
            $stackMessage = $stackMessages->get($stackCursor);
            /** @var ?Message $newMessage */
            $newMessage = $newMessages->get($parserCursor);

            if ($stackMessage) {
                if ($newMessage === null || abs(Date::now()->diffInSeconds($stackMessage->date())) >= $this->screenTTL) {
                    $deleteMessages[$stackCursor] = $stackMessage->id();
                } elseif ($newMessage->signature() === $stackMessage->signature()) {
                    $parserCursor++;
                    $array[] = $stackMessage;
                } elseif (!$this->api->canEdit($stackMessage, $newMessage)) {
                    $deleteMessages[$stackCursor] = $stackMessage->id();
                } else {
                    try {
                        $editedMessage = $this->api->edit($stackMessage, $newMessage);
                        $this->stack->put($stackCursor, $editedMessage);

                        $parserCursor++;
                        $array[] = $editedMessage;
                    } catch (\Exception $e) {
                        Log::error($e);

                        $deleteMessages[$stackCursor] = $stackMessage->id();
                    }
                }
                $stackCursor++;
            } elseif ($newMessage !== null) {
                $parserCursor++;

                $message = $this->api->send($newMessage);
                $this->stack->push($message);

                $array[] = $message;
            } else {
                break;
            }
        }

        $array = [
            ...$array,
            ...$this->classic(),
        ];

        if (count($deleteMessages) > 0) {
            $this->api->try('deleteMessages', array_values($deleteMessages));
            $this->stack->forget(array_keys($deleteMessages));
        }

        return $array;
    }

    protected function mixed(): array
    {
        $array = [];

        $deleteMessages = [];
        $stackMessages = $this->stack->collect();
        $newMessages = $this->parser->screenMessages;
        $removeAll = false;

        foreach ($newMessages as $i => $newMessage) {
            $stackMessage = $stackMessages->get($i);

            if ($stackMessage === null || $removeAll) {
                $message = $this->api->send($newMessage);
                $this->stack->push($message);

                $array[] = $message;
            } elseif ($stackMessage->signature() !== $newMessage->signature()) {
                $removeAll = true;

                foreach ($stackMessages as $j => $item) {
                    if ($j >= $i) {
                        $deleteMessages[$j] = $item->id();
                    }
                }

                $message = $this->api->send($newMessage);
                $this->stack->push($message);

                $array[] = $message;
            }
        }

        $array = [
            ...$array,
            ...$this->classic(),
        ];
        ;

        if (count($deleteMessages) > 0) {
            $this->api->try('deleteMessages', array_values($deleteMessages));
            $this->stack->forget(array_keys($deleteMessages));
        }

        return $array;
    }
}
