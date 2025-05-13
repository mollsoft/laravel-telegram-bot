<?php

declare(strict_types=1);

namespace Mollsoft\Telegram;

use danog\TelegramEntities\Entities;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Mollsoft\Telegram\DTO\CallbackQuery;
use Mollsoft\Telegram\DTO\Contact;
use Mollsoft\Telegram\DTO\Document;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\DTO\PhotoSize;
use Mollsoft\Telegram\DTO\VideoFile;
use Mollsoft\Telegram\DTO\VideoNoteFile;
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
    protected ?VideoFile $video = null;
    protected ?Contact $contact = null;
    protected ?VoiceNote $voice = null;
    protected ?VideoNoteFile $videoNote = null;
    protected ?TelegramAttachment $attachment = null;
    protected string $messageHTML = '';

    protected ChatAPI $api;
    protected Storage $storage;
    protected MessageStack $stack;
    protected ?array $live = null;

    protected ?int $livePeriod = null;
    protected ?Carbon $liveLaunchAt = null, $liveExpiredAt = null;

    public static function createFromTelegram(
        TelegramBot $bot,
        TelegramChat $chat,
        string $uri,
        ?Message $message = null,
        ?CallbackQuery $callbackQuery = null,
    ): static {
        $appURL = config('app.url');
        $appHost = parse_url($appURL, PHP_URL_HOST);
        $appScheme = parse_url($appURL, PHP_URL_SCHEME);

        $server = [
            'SERVER_NAME' => $appHost,
            'HTTP_HOST' => $appHost,
            'SERVER_PORT' => $appScheme === 'https' ? 443 : 80,
        ];
        if ($appScheme === 'https') {
            $server['HTTPS'] = 'on';
        }

        try {
            $request = static::create(
                uri: $uri,
                method: 'TELEGRAM',
                parameters: $parameters ?? [],
                cookies: [],
                files: [],
                server: $server
            );
        }
        catch(\Exception) {
            $request = static::create(
                uri: '/',
                method: 'TELEGRAM',
                parameters: $parameters ?? [],
                cookies: [],
                files: [],
                server: $server
            );
        }

        return $request
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
        if (!$text && ($this->message instanceof HasCaption)) {
            $text = $this->message->caption();
            $entities = $this->message->captionEntities();
        }

        $textWithEntities = !is_null($text) ? htmlspecialchars($text) : null;
        if ($text && $entities) {
            foreach ($entities as $i => $item) {
                if (($item['type'] ?? null) === 'blockquote') {
                    $entities[$i]['type'] = 'block_quote';
                }
                if( isset( $item['offset'] ) ) {
                    $entities[$i]['offset'] = (int)$item['offset'];
                }
                if( isset( $item['length'] ) ) {
                    $entities[$i]['length'] = (int)$item['length'];
                }
            }
            $telegramEntities = new Entities($text, $entities);
            $textWithEntities = str_replace("<br>", "\n", $telegramEntities->toHTML(true));
        }

        $inlineKeyboardHTML = '';
        if (($inlineKeyboard = $message?->inlineKeyboard()?->toArray()) && count(
                $inlineKeyboard['inline_keyboard'] ?? []
            ) > 0) {
            $inlineKeyboard = $inlineKeyboard['inline_keyboard'];
            $inlineKeyboardHTML = '<inline-keyboard>';
            foreach ($inlineKeyboard as $columns) {
                $inlineKeyboardHTML .= '<row>';
                foreach (is_array($columns) ? $columns : [$columns] as $column) {
                    $inlineKeyboardHTML .= '<column url="'.($column['url'] ?? config(
                            'app.url'
                        )).'">'.($column['text'] ?? print_r($column, true)).'</column>';
                }
                $inlineKeyboardHTML .= '</row>';
            }
            $inlineKeyboardHTML .= '</inline-keyboard>';
        }

        $html = $textWithEntities ? '<message><lines>'.$textWithEntities.'</lines>'.$inlineKeyboardHTML.'</message>' : '';

        $photo = null;
        if ($this->message instanceof Message\Photo) {
            /** @var ?PhotoSize $photo */
            $photo = $this->message
                ->photo()
                ->sortByDesc(fn(PhotoSize $item) => $item->width())
                ->first();
            if ($photo) {
                $tags = [
                    'src="'.$photo->fileId().'"',
                ];
                if (($value = $this->message->showCaptionAboveMedia()) !== null) {
                    $tags[] = 'show_caption_above_media="'.($value ? 1 : 0).'"';
                }
                $html = '<photo '.implode(
                        ' ',
                        $tags
                    ).'>'.($textWithEntities ? '<lines>'.$textWithEntities.'</lines>' : '').$inlineKeyboardHTML.'</photo>';
            }
        }

        $document = null;
        if ($this->message instanceof Message\Document) {
            $document = $this->message->document();
            if ($document) {
                $html = '<document src="'.$document->fileId(
                    ).'">'.($textWithEntities ? '<lines>'.$textWithEntities.'</lines>' : '').$inlineKeyboardHTML.'</document>';
            }
        }

        $voice = null;
        if ($this->message instanceof Message\Voice) {
            $voice = $this->message->voiceNote();
            if ($voice) {
                $html = '<voice src="'.$voice->fileId(
                    ).'">'.($textWithEntities ? '<line>'.$textWithEntities.'</line>' : '').$inlineKeyboardHTML.'</voice>';
            }
        }

        $video = null;
        if ($this->message instanceof Message\Video) {
            $video = $this->message->video();
            if ($video) {
                $tags = [
                    'src="'.$video->fileId().'"',
                ];
                if (($value = $this->message->showCaptionAboveMedia()) !== null) {
                    $tags[] = 'show_caption_above_media="'.($value ? 1 : 0).'"';
                }
                $html = '<video '.implode(
                        ' ',
                        $tags
                    ).'>'.($textWithEntities ? '<line>'.$textWithEntities.'</line>' : '').$inlineKeyboardHTML.'</video>';
            }
        }

        $videoNote = null;
        if ($this->message instanceof Message\VideoNote) {
            $videoNote = $this->message->videoNote();
            if ($videoNote) {
                $html = '<video-note src="'.$videoNote->fileId().'">'.$inlineKeyboardHTML.'</video-note>';
            }
        }

        return $this
            ->setMessageHTML($html)
            ->setText($text)
            ->setPhoto($photo)
            ->setDocument($document)
            ->setContact($this->message?->contact())
            ->setVoice($voice)
            ->setVideo($video)
            ->setVideoNote($videoNote);
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
        if ($this->voice && !$voice) {
            $this->attachment = null;
        }

        $this->voice = $voice;

        if ($voice) {
            $this->attachment = new TelegramAttachment([
                'bot_id' => $this->bot->id,
                'chat_id' => $this->chat->chat_id,
                'type' => 'voice',
                'caption' => $this->text,
                'data' => $this->voice->toArray(),
            ]);
        }

        return $this;
    }

    public function voice(): ?VoiceNote
    {
        return $this->voice;
    }

    public function video(): ?VideoFile
    {
        return $this->video;
    }

    public function setVideoNote(?VideoNoteFile $videoNote): static
    {
        if ($this->videoNote && !$videoNote) {
            $this->attachment = null;
        }

        $this->videoNote = $videoNote;

        if ($videoNote) {
            $this->attachment = new TelegramAttachment([
                'bot_id' => $this->bot->id,
                'chat_id' => $this->chat->chat_id,
                'type' => 'video_note',
                'caption' => $this->text,
                'data' => $this->videoNote->toArray(),
            ]);
        }

        return $this;
    }

    public function videoNote(): ?VideoNoteFile
    {
        return $this->videoNote;
    }

    public function setVideo(?VideoFile $video): static
    {
        if ($this->video && !$video) {
            $this->attachment = null;
        }

        $this->video = $video;

        if ($video) {
            $this->attachment = new TelegramAttachment([
                'bot_id' => $this->bot->id,
                'chat_id' => $this->chat->chat_id,
                'type' => 'video',
                'caption' => $this->text,
                'data' => $this->video->toArray(),
            ]);
        }

        return $this;
    }

    public function setDocument(?Document $document): static
    {
        if ($this->document && !$document) {
            $this->attachment = null;
        }

        $this->document = $document;

        if ($document) {
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
        if ($this->photoSize && !$photoSize) {
            $this->attachment = null;
        }

        $this->photoSize = $photoSize;

        if ($photoSize) {
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

    public function live(?int $period = null, int $timeout = 3600): static
    {
        $this->livePeriod = $period;
        $this->liveLaunchAt = $period ? Date::now()->addSeconds($period) : null;
        $this->liveExpiredAt = $period ? Date::now()->addSeconds($timeout) : null;

        return $this;
    }

    public function livePeriod(): ?int
    {
        return $this->livePeriod;
    }

    public function liveLaunchAt(): ?Carbon
    {
        return $this->liveLaunchAt;
    }

    public function liveExpireAt(): ?Carbon
    {
        return $this->liveExpiredAt;
    }
}
