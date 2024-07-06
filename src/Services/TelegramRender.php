<?php

namespace Mollsoft\Telegram\Services;

use Illuminate\Support\Facades\Log;
use Mollsoft\Telegram\ChatAPI;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\MessageStack;

readonly class TelegramRender
{
    public function __construct(
        protected ChatAPI $api,
        protected MessageStack $stack,
        protected HTMLParser $parser
    ) {
    }

    public function run(): void
    {
        match ($this->parser->type()) {
            'screen' => $this->screen(),
            'mixed' => $this->mixed(),
            'classic' => $this->classic(),
        };
    }

    protected function classic(): void
    {
        foreach ($this->parser->appendMessages as $item) {
            $message = $this->api->send($item);
            $this->stack->push($message);
        }
    }

    protected function screen(): void
    {
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
                if ($newMessage === null) {
                    $deleteMessages[$stackCursor] = $stackMessage->id();
                } elseif ($newMessage->signature() === $stackMessage->signature()) {
                    $parserCursor++;
                } elseif (!$this->api->canEdit($stackMessage, $newMessage)) {
                    $deleteMessages[$stackCursor] = $stackMessage->id();
                } else {
                    try {
                        $editedMessage = $this->api->edit($stackMessage, $newMessage);
                        $this->stack->put($stackCursor, $editedMessage);

                        $parserCursor++;
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
            } else {
                break;
            }
        }

        $this->classic();

        if (count($deleteMessages) > 0) {
            $this->api->try('deleteMessages', array_values($deleteMessages));
            $this->stack->forget(array_keys($deleteMessages));
        }
    }

    protected function mixed(): void
    {
        $deleteMessages = [];
        $stackMessages = $this->stack->collect();
        $newMessages = $this->parser->screenMessages;
        $removeAll = false;

        foreach ($newMessages as $i => $newMessage) {
            $stackMessage = $stackMessages->get($i);

            if ($stackMessage === null || $removeAll) {
                $message = $this->api->send($newMessage);
                $this->stack->push($message);
            } elseif ($stackMessage->signature() !== $newMessage->signature()) {
                $removeAll = true;

                foreach ($stackMessages as $j => $item) {
                    if ($j >= $i) {
                        $deleteMessages[$j] = $item->id();
                    }
                }

                $message = $this->api->send($newMessage);
                $this->stack->push($message);
            }
        }

        $this->classic();

        if (count($deleteMessages) > 0) {
            $this->api->try('deleteMessages', array_values($deleteMessages));
            $this->stack->forget(array_keys($deleteMessages));
        }
    }
}
