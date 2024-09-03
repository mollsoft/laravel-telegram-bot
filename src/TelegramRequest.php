<?php

declare(strict_types=1);

namespace Mollsoft\Telegram;

use Illuminate\Support\Facades\Log;
use Mollsoft\Telegram\DTO\CallbackQuery;
use Mollsoft\Telegram\DTO\Contact;
use Mollsoft\Telegram\DTO\Document;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\DTO\PhotoSize;
use Mollsoft\Telegram\DTO\VoiceNote;
use Mollsoft\Telegram\Interfaces\HasCaption;
use Mollsoft\Telegram\Models\TelegramAttachment;
use Mollsoft\Telegram\Models\TelegramBot;
use Mollsoft\Telegram\Models\TelegramChat;
use Symfony\Component\HttpFoundation\InputBag;

class TelegramRequest extends \Illuminate\Http\Request
{
    protected TelegramBot $bot;
    protected TelegramChat $chat;
    protected ?Message $message = null;
    protected ?string $text = null;
    protected ?CallbackQuery $callbackQuery = null;
    protected ?PhotoSize $photoSize = null;
    protected ?Document $document = null;
    protected ?Contact $contact = null;
    protected ?VoiceNote $voice = null;
    protected ?TelegramAttachment $attachment = null;
    protected string $messageHTML = '';

    protected ChatAPI $api;
    protected Storage $storage;
    protected MessageStack $stack;

    public static function createFromTelegram(
        TelegramBot $bot,
        TelegramChat $chat,
        string $uri,
        ?Message $message = null,
        ?CallbackQuery $callbackQuery = null,
    ): static {
        return static::create(
            uri: $uri,
            method: 'TELEGRAM',
            parameters: $parameters ?? []
        )
            ->setBot($bot)
            ->setChat($chat)
            ->setMessage($message)
            ->setCallbackQuery($callbackQuery);
    }

    public function setMessage(?Message $message): static
    {
        $this->message = $message;

        $text = $this->message?->text();
        $entities = $this->message?->entities();
        if( !$text && ($this->message instanceof HasCaption) ) {
            $text = $this->message->caption();
            $entities = $this->message->captionEntities();
        }

        $textWithEntities = $text && $entities ? $this->applyEntities($text, $entities) : (!is_null($text) ? htmlspecialchars($text) : null);

        $html = $textWithEntities ? '<message><lines>'.$textWithEntities.'</lines></message>' : '';

        $photo = null;
        if( $this->message instanceof Message\Photo ) {
            /** @var ?PhotoSize $photo */
            $photo = $this->message
                ->photo()
                ->sortByDesc(fn(PhotoSize $item) => $item->width())
                ->first();
            if( $photo ) {
                $html = '<photo src="'.$photo->fileId().'">'.($textWithEntities ? '<lines>'.$textWithEntities.'</lines>' : '').'</photo>';
            }
        }

        $document = null;
        if( $this->message instanceof Message\Document ) {
            $document = $this->message->document();
            if( $document ) {
                $html = '<document src="'.$document->fileId().'">'.($textWithEntities ? '<lines>'.$textWithEntities.'</lines>' : '').'</document>';
            }
        }

        $voice = null;
        if( $this->message instanceof Message\Voice ) {
            $voice = $this->message->voiceNote();
            if( $voice ) {
                $html = '<voice src="'.$voice->fileId().'">'.($textWithEntities ? '<line>'.$textWithEntities.'</line>' : '').'</voice>';
            }
        }

        return $this
            ->setMessageHTML($html)
            ->setText($text)
            ->setPhoto($photo)
            ->setDocument($document)
            ->setContact($this->message?->contact())
            ->setVoice($voice);
    }

    protected function applyEntities(string $text, array $entities): string
    {
        $typeToTag = [
            'bold' => 'b',
            'underline' => 'u',
            'strikethrough' => 's',
            'spoiler' => 'tg-spoiler',
            'code' => 'code',
            'italic' => 'i',
            'blockquote' => 'blockquote',
            'text_link' => 'a',
        ];

        // Массив для хранения тегов
        $openTags = [];
        $closeTags = [];

        // Обрабатываем каждый entity
        foreach ($entities as $entity) {
            $start = $entity['offset'];
            $end = $start + $entity['length'];
            $type = $entity['type'];

            // Добавляем открывающие теги в нужную позицию
            if (!isset($openTags[$start])) {
                $openTags[$start] = '';
            }
            if( $typeToTag[$type] ?? null ) {
                $tag = $typeToTag[$type];
                if( $tag === 'a' ) {
                    $tag .= ' href="'.($entity['url'] ?? 'https://t.me').'"';
                }
                $openTags[$start] .= '<'.$tag.'>';
            }

            // Добавляем закрывающие теги в нужную позицию
            if (!isset($closeTags[$end])) {
                $closeTags[$end] = '';
            }
            if( $typeToTag[$type] ?? null ) {
                $closeTags[$end] = '</'.$typeToTag[$type].'>' . $closeTags[$end];
            }
        }

        // Формируем финальный текст с тегами
        $result = '';
        for ($i = 0; $i < mb_strlen($text); $i++) {
            // Добавляем открывающие теги перед текущим символом, если они есть
            if (isset($openTags[$i])) {
                $result .= $openTags[$i];
            }

            // Добавляем текущий символ
            $result .= htmlspecialchars(mb_substr($text, $i, 1));

            // Добавляем закрывающие теги после текущего символа, если они есть
            if (isset($closeTags[$i + 1])) {
                $result .= $closeTags[$i + 1];
            }
        }

        return $result;
    }

    public function message(): ?Message
    {
        return $this->message;
    }

    public function attachment(): ?TelegramAttachment
    {
        return $this->attachment;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function contact(): ?Contact
    {
        return $this->contact;
    }

    public function setVoice(?VoiceNote $voice): static
    {
        $this->voice = $voice;

        return $this;
    }

    public function voice(): ?VoiceNote
    {
        return $this->voice;
    }

    public function setDocument(?Document $document): static
    {
        if( $this->document && !$document ) {
            $this->attachment = null;
        }

        $this->document = $document;

        if( $document ) {
            $this->attachment = new TelegramAttachment([
                'bot_id' => $this->bot->id,
                'chat_id' => $this->chat->chat_id,
                'type' => 'document',
                'caption' => $this->text,
                'data' => $this->document->toArray(),
            ]);
        }

        return $this;
    }

    public function document(): ?Document
    {
        return $this->document;
    }

    public function setPhoto(?PhotoSize $photoSize): static
    {
        if( $this->photoSize && !$photoSize ) {
            $this->attachment = null;
        }

        $this->photoSize = $photoSize;

        if( $photoSize ) {
            $this->attachment = new TelegramAttachment([
                'bot_id' => $this->bot->id,
                'chat_id' => $this->chat->chat_id,
                'type' => 'photo',
                'caption' => $this->text,
                'data' => $this->photoSize->toArray(),
            ]);
        }

        return $this;
    }

    public function photo(): ?PhotoSize
    {
        return $this->photoSize;
    }

    public function setBot(TelegramBot $bot): static
    {
        $this->bot = $bot;

        return $this;
    }

    public function setChat(TelegramChat $chat): static
    {
        $this->chat = $chat;
        $this->api = new ChatAPI($this->bot->token, $this->chat->chat_id);
        $this->storage = new Storage(get_class($chat).'_'.$chat->getKey());
        $this->stack = new MessageStack($chat);

        return $this;
    }

    public function bot(): TelegramBot
    {
        return $this->bot;
    }

    public function chat(): TelegramChat
    {
        return $this->chat;
    }

    public function api(): ChatAPI
    {
        return $this->api;
    }

    public function storage(): Storage
    {
        return $this->storage;
    }

    public function stack(): MessageStack
    {
        return $this->stack;
    }

    public function setMessageHTML(string $messageHTML): static
    {
        $this->messageHTML = $messageHTML;

        return $this;
    }

    public function messageHTML(): string
    {
        return $this->messageHTML;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function callbackQuery(): ?CallbackQuery
    {
        return $this->callbackQuery;
    }

    public function setCallbackQuery(?CallbackQuery $callbackQuery): static
    {
        $this->callbackQuery = $callbackQuery;
        if ($callbackQuery) {
            $this->request = new InputBag($callbackQuery->getAllData());
        }

        return $this;
    }

    public function hasText(): bool
    {
        return !empty($this->text);
    }

    public function text(): ?string
    {
        return $this->text;
    }
}
