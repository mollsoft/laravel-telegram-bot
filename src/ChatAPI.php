<?php

namespace Mollsoft\Telegram;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Mollsoft\Telegram\Abstract\ApiClient;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\DTO\Message\Document;
use Mollsoft\Telegram\DTO\Message\Photo;
use Mollsoft\Telegram\DTO\Message\Video;
use Mollsoft\Telegram\DTO\Message\VideoNote;
use Mollsoft\Telegram\DTO\Message\Voice;
use Mollsoft\Telegram\DTO\UserProfilePhotos;
use Mollsoft\Telegram\Enums\ChatAction;
use Mollsoft\Telegram\Interfaces\HasCaption;

class ChatAPI extends ApiClient
{
    public function __construct(string $token, protected readonly int|string $chatId)
    {
        $baseUri = config('telegram.api.base_uri', 'https://api.telegram.org');

        $client = Http::baseUrl("$baseUri/bot$token/")
            ->connectTimeout(config('telegram.api.connect_timeout', 20))
            ->timeout(config('telegram.api.timeout', 60));

        return parent::__construct($client);
    }

    public function deleteMessages(int|array ...$messageIds): bool
    {
        $messageIds = Arr::flatten($messageIds);

        return $this->sendRequest('deleteMessages', [
            'chat_id' => $this->chatId,
            'message_ids' => array_map('intval', $messageIds),
        ])[0];
    }

    public function getUserProfilePhotos(int $limit = 100, int $offset = 0): UserProfilePhotos
    {
        $data = $this->sendRequest('getUserProfilePhotos', [
            'user_id' => $this->chatId,
            'offset' => $offset,
            'limit' => $limit,
        ]);

        return UserProfilePhotos::fromArray($data);
    }

    public function send(Message $message): Message
    {
        $data = [
            'chat_id' => $this->chatId,
            'parse_mode' => 'html',
        ];

        if( $message->replyParameters() ) {
            $data['reply_parameters'] = $message->replyParameters()->toArray();
        }

        if ($message->replyKeyboard()) {
            $data['reply_markup'] = $message->replyKeyboard()->toArray();
        }

        if ($message->inlineKeyboard()) {
            $data['reply_markup'] = $message->inlineKeyboard()->toArray();
        }

        if ($message instanceof Photo) {
            if ($value = $message->caption()) {
                $data['caption'] = $value;
            }
            if( $value = $message->showCaptionAboveMedia() ) {
                $data['show_caption_above_media'] = boolval($value);
            }

            $src = $message->photoSrc();
            $hash = null;
            if (File::exists($src)) {
                $hash = hash_file('sha256', $src);
                $cacheSrc = Cache::get('telegram_'.$hash);
                $src = $cacheSrc ?: fopen($src, 'r');
            }

            try {
                $responseData = $this->sendRequestMultipart('sendPhoto', [
                    ...$data,
                    'photo' => $src,
                ]);
            } catch (\Exception) {
                $src = $message->photoSrc();
                if (File::exists($src)) {
                    $src = fopen($src, 'r');
                }

                $responseData = $this->sendRequestMultipart('sendPhoto', [
                    ...$data,
                    'photo' => $src,
                ]);
            }

            if ($hash && ($photo = $responseData['photo'] ?? null)) {
                Cache::set(
                    'telegram_'.$hash,
                    $photo[count($photo) - 1]['file_id'],
                    (int)config('telegram.cache.ttl', 86400)
                );
            }

            $responseData['photo_src'] = $message->photoSrc();
        } elseif ($message instanceof Video) {
            if ($value = $message->caption()) {
                $data['caption'] = $value;
            }
            if ($value = $message->showCaptionAboveMedia()) {
                $data['show_caption_above_media'] = boolval($value);
            }

            $src = $message->videoSrc();
            $hash = null;
            if (File::exists($src)) {
                $hash = hash_file('sha256', $src);
                $cacheSrc = Cache::get('telegram_'.$hash);
                $src = $cacheSrc ?: fopen($src, 'r');
            }

            try {
                $responseData = $this->sendRequestMultipart('sendVideo', [
                    ...$data,
                    'video' => $src,
                ]);
            } catch (\Exception) {
                $src = $message->videoSrc();
                if (File::exists($src)) {
                    $src = fopen($src, 'r');
                }

                $responseData = $this->sendRequestMultipart('sendVideo', [
                    ...$data,
                    'video' => $src,
                ]);
            }

            if ($hash && ($video = $responseData['video'] ?? null)) {
                Cache::set('telegram_'.$hash, $video['file_id'], (int)config('telegram.cache.ttl', 86400));
            }

            $responseData['video_src'] = $message->videoSrc();
        } elseif ($message instanceof VideoNote) {
            $src = $message->videoNoteSrc();
            $hash = null;
            if (File::exists($src)) {
                $hash = hash_file('sha256', $src);
                $cacheSrc = Cache::get('telegram_'.$hash);
                $src = $cacheSrc ?: fopen($src, 'r');
            }

            try {
                $responseData = $this->sendRequestMultipart('sendVideoNote', [
                    ...$data,
                    'video_note' => $src,
                ]);
            } catch (\Exception) {
                $src = $message->videoNoteSrc();
                if (File::exists($src)) {
                    $src = fopen($src, 'r');
                }

                $responseData = $this->sendRequestMultipart('sendVideoNote', [
                    ...$data,
                    'video_note' => $src,
                ]);
            }

            if ($hash && ($videoNote = $responseData['video_note'] ?? null)) {
                Cache::set('telegram_'.$hash, $videoNote['file_id'], (int)config('telegram.cache.ttl', 86400));
            }

            $responseData['video_note_src'] = $message->videoNoteSrc();
        } elseif ($message instanceof Voice) {
            if ($caption = $message->caption()) {
                $data['caption'] = $caption;
            }

            $src = $message->voiceSrc();
            $hash = null;
            if (File::exists($src)) {
                $hash = hash_file('sha256', $src);
                $cacheSrc = Cache::get('telegram_'.$hash);
                $src = $cacheSrc ?: fopen($src, 'r');
            }

            try {
                $responseData = $this->sendRequestMultipart('sendVoice', [
                    ...$data,
                    'voice' => $src,
                ]);
            } catch (\Exception) {
                $src = $message->voiceSrc();
                if (File::exists($src)) {
                    $src = fopen($src, 'r');
                }

                $responseData = $this->sendRequestMultipart('sendVoice', [
                    ...$data,
                    'voice' => $src,
                ]);
            }

            if ($hash && ($voice = $responseData['voice'] ?? null)) {
                Cache::set('telegram_'.$hash, $voice['file_id'], (int)config('telegram.cache.ttl', 86400));
            }

            $responseData['voice_src'] = $message->voiceSrc();
        } elseif ($message instanceof Document) {
            if ($caption = $message->caption()) {
                $data['caption'] = $caption;
            }

            $src = $message->documentSrc();
            $hash = null;
            if (File::exists($src)) {
                $hash = hash_file('sha256', $src);
                $cacheSrc = Cache::get('telegram_'.$hash);
                $src = $cacheSrc ?: fopen($src, 'r');
            }

            try {
                $responseData = $this->sendRequestMultipart('sendDocument', [
                    ...$data,
                    'document' => $src,
                ]);
            } catch (\Exception) {
                $src = $message->documentSrc();
                if (File::exists($src)) {
                    $src = fopen($src, 'r');
                }

                $responseData = $this->sendRequestMultipart('sendDocument', [
                    ...$data,
                    'document' => $src,
                ]);
            }

            if ($hash && ($document = $responseData['document'] ?? null)) {
                Cache::set('telegram_'.$hash, $document['file_id'], (int)config('telegram.cache.ttl', 86400));
            }

            $responseData['document_src'] = $message->documentSrc();
        } else {
            if ($message->text()) {
                $data['text'] = $message->text();
            }

            $responseData = $this->sendRequestMultipart('sendMessage', $data);
        }

        if (isset($data['reply_markup'])) {
            $responseData['reply_markup'] = $data['reply_markup'];
        }

        return Message::fromArray($responseData);
    }

    public function canEdit(Message $old, Message $new): bool
    {
        if (!$old->from()?->isBot()) {
            return false;
        }

        // Мы не можем редактировать сообщения в которых есть Reply Keyboard
        if ($old->replyKeyboard() || $new->replyKeyboard()) {
            return false;
        }

        // Если одно из сообщений картинка а второе нет - не можем редактировать
        if (
            (($old instanceof Photo) && !($new instanceof Photo))
            ||
            (!($old instanceof Photo) && ($new instanceof Photo))
        ) {
            return false;
        }

        if (
            (($old instanceof Video) && !($new instanceof Video))
            ||
            (!($old instanceof Video) && ($new instanceof Video))
        ) {
            return false;
        }

        if (
            (($old instanceof Message\Voice) && !($new instanceof Message\Voice))
            ||
            (!($old instanceof Message\Voice) && ($new instanceof Message\Voice))
        ) {
            return false;
        }

        if (
            (($old instanceof Document) && !($new instanceof Document))
            ||
            (!($old instanceof Document) && ($new instanceof Document))
        ) {
            return false;
        }

        if ($old->text() && $new->text()) {
            $oldReplyKeyboard = json_encode($old->replyKeyboard()?->toArray() ?? []);
            $newReplyKeyboard = json_encode($new->replyKeyboard()?->toArray() ?? []);
            if ($oldReplyKeyboard !== $newReplyKeyboard) {
                return false;
            }

            return true;
        }

        if ($old instanceof HasCaption && $new instanceof HasCaption && $old->caption() && $new->caption()) {
            $oldReplyKeyboard = json_encode($old->replyKeyboard()?->toArray() ?? []);
            $newReplyKeyboard = json_encode($new->replyKeyboard()?->toArray() ?? []);
            if ($oldReplyKeyboard !== $newReplyKeyboard) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function edit(Message $old, Message $new): Message
    {
        if (!$this->canEdit($old, $new)) {
            throw new \Exception("Can't edit message by rules.");
        }

        if ($old instanceof Photo && $new instanceof Photo) {
            if ($old->photoSrc() !== $new->photoSrc()) {
                $src = $new->photoSrc();
                $hash = null;
                if (File::exists($src)) {
                    $hash = hash_file('sha256', $src);
                    $cacheSrc = Cache::get('telegram_'.$hash);
                    $src = $cacheSrc ?: fopen($src, 'r');
                }

                $data = [
                    'chat_id' => $this->chatId,
                    'message_id' => $old->id(),
                    'media' => [
                        'type' => 'photo',
                        'media' => $src,
                        'caption' => $new->caption(),
                        'parse_mode' => 'html',
                    ],
                    'reply_markup' => $new->inlineKeyboard()?->toArray() ?? ['inline_keyboard' => []]
                ];
                if ($value = $new->showCaptionAboveMedia()) {
                    $data['media']['show_caption_above_media'] = boolval($value);
                }

                $responseData = $this->sendRequestMultipart('editMessageMedia', $data);

                if ($hash && ($photo = $responseData['photo'] ?? null)) {
                    Cache::set(
                        'telegram_'.$hash,
                        $photo[count($photo) - 1]['file_id'],
                        (int)config('telegram.cache.ttl', 86400)
                    );
                }
            } elseif (
                ($old->captionSignature() !== $new->captionSignature())
                ||
                ($old->replyMarkupSignature() !== $new->replyMarkupSignature())
            ) {
                $responseData = $this->sendRequest('editMessageCaption', [
                    'chat_id' => $this->chatId,
                    'message_id' => $old->id(),
                    'caption' => $new->caption(),
                    'parse_mode' => 'html',
                    'reply_markup' => $new->inlineKeyboard()?->toArray() ?? ['inline_keyboard' => []]
                ]);
            }

            $responseData['photo_src'] = $new->photoSrc();
        } elseif ($old instanceof Video && $new instanceof Video) {
            if ($old->videoSrc() !== $new->videoSrc()) {
                $src = $new->videoSrc();
                $hash = null;
                if (File::exists($src)) {
                    $hash = hash_file('sha256', $src);
                    $cacheSrc = Cache::get('telegram_'.$hash);
                    $src = $cacheSrc ?: fopen($src, 'r');
                }

                $data = [
                    'chat_id' => $this->chatId,
                    'message_id' => $old->id(),
                    'media' => [
                        'type' => 'video',
                        'media' => $src,
                        'caption' => $new->caption(),
                        'parse_mode' => 'html',
                    ],
                    'reply_markup' => $new->inlineKeyboard()?->toArray() ?? ['inline_keyboard' => []]
                ];
                if ($value = $new->showCaptionAboveMedia()) {
                    $data['media']['show_caption_above_media'] = boolval($value);
                }
                $responseData = $this->sendRequestMultipart('editMessageMedia', $data);

                if ($hash && ($video = $responseData['video'] ?? null)) {
                    Cache::set('telegram_'.$hash, $video['file_id'], (int)config('telegram.cache.ttl', 86400));
                }
            } elseif (
                ($old->captionSignature() !== $new->captionSignature())
                ||
                ($old->replyMarkupSignature() !== $new->replyMarkupSignature())
            ) {
                $data = [
                    'chat_id' => $this->chatId,
                    'message_id' => $old->id(),
                    'caption' => $new->caption(),
                    'parse_mode' => 'html',
                    'reply_markup' => $new->inlineKeyboard()?->toArray() ?? ['inline_keyboard' => []]
                ];
                if ($value = $new->showCaptionAboveMedia()) {
                    $data['show_caption_above_media'] = boolval($value);
                }
                $responseData = $this->sendRequest('editMessageCaption', $data);
            }

            $responseData['video_src'] = $new->videoSrc();
        } elseif ($old instanceof Message\Voice && $new instanceof Message\Voice) {
            if ($old->voiceSrc() !== $new->voiceSrc()) {
                $src = $new->voiceSrc();
                $hash = null;
                if (File::exists($src)) {
                    $hash = hash_file('sha256', $src);
                    $cacheSrc = Cache::get('telegram_'.$hash);
                    $src = $cacheSrc ?: fopen($src, 'r');
                }

                $responseData = $this->sendRequestMultipart('editMessageMedia', [
                    'chat_id' => $this->chatId,
                    'message_id' => $old->id(),
                    'media' => [
                        'type' => 'audio',
                        'media' => $src,
                        'caption' => $new->caption(),
                        'parse_mode' => 'html',
                    ],
                    'reply_markup' => $new->inlineKeyboard()?->toArray() ?? ['inline_keyboard' => []]
                ]);

                if ($hash && ($voice = $responseData['voice'] ?? null)) {
                    Cache::set('telegram_'.$hash, $voice['file_id'], (int)config('telegram.cache.ttl', 86400));
                }
            } elseif (
                ($old->captionSignature() !== $new->captionSignature())
                ||
                ($old->replyMarkupSignature() !== $new->replyMarkupSignature())
            ) {
                $responseData = $this->sendRequest('editMessageCaption', [
                    'chat_id' => $this->chatId,
                    'message_id' => $old->id(),
                    'caption' => $new->caption(),
                    'parse_mode' => 'html',
                    'reply_markup' => $new->inlineKeyboard()?->toArray() ?? ['inline_keyboard' => []]
                ]);
            }

            $responseData['voice_src'] = $new->voiceSrc();
        } elseif ($old instanceof Document && $new instanceof Document) {
            if ($old->documentSrc() !== $new->documentSrc()) {
                $src = $new->documentSrc();
                $hash = null;
                if (File::exists($src)) {
                    $hash = hash_file('sha256', $src);
                    $cacheSrc = Cache::get('telegram_'.$hash);
                    $src = $cacheSrc ?: fopen($src, 'r');
                }

                $responseData = $this->sendRequestMultipart('editMessageMedia', [
                    'chat_id' => $this->chatId,
                    'message_id' => $old->id(),
                    'media' => [
                        'type' => 'document',
                        'media' => $src,
                        'caption' => $new->caption(),
                        'parse_mode' => 'html',
                    ],
                    'reply_markup' => $new->inlineKeyboard()?->toArray() ?? ['inline_keyboard' => []]
                ]);

                if ($hash && ($document = $responseData['document'] ?? null)) {
                    Cache::set('telegram_'.$hash, $document['file_id'], (int)config('telegram.cache.ttl', 86400));
                }
            } elseif (
                ($old->captionSignature() !== $new->captionSignature())
                ||
                ($old->replyMarkupSignature() !== $new->replyMarkupSignature())
            ) {
                $responseData = $this->sendRequest('editMessageCaption', [
                    'chat_id' => $this->chatId,
                    'message_id' => $old->id(),
                    'caption' => $new->caption(),
                    'parse_mode' => 'html',
                    'reply_markup' => $new->inlineKeyboard()?->toArray() ?? ['inline_keyboard' => []]
                ]);
            }

            $responseData['document_src'] = $new->documentSrc();
        } else {
            $responseData = $this->sendRequest('editMessageText', [
                'chat_id' => $this->chatId,
                'message_id' => $old->id(),
                'text' => $new->text(),
                'parse_mode' => 'html',
                'reply_markup' => $new->inlineKeyboard()?->toArray() ?? ['inline_keyboard' => []]
            ]);
        }

        if ($old->replyKeyboard()) {
            $responseData['reply_markup'] = $old->replyKeyboard()->toArray();
        }

        return Message::fromArray($responseData);
    }

    public function delete(Message|array ...$messages): bool
    {
        /** @var Message[] $messages */
        $messages = Arr::flatten($messages);

        $messageIds = array_map(fn(Message $item) => $item->id(), $messages);

        return $this->sendRequest('deleteMessages', [
            'chat_id' => $this->chatId,
            'message_ids' => array_map('intval', $messageIds),
        ])[0];
    }

    public function sendChatAction(ChatAction $action): bool
    {
        return $this->sendRequest('sendChatAction', [
            'chat_id' => $this->chatId,
            'action' => $action->value,
        ])[0];
    }
}
