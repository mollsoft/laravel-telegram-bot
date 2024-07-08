<?php

namespace Mollsoft\Telegram;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Mollsoft\Telegram\Abstract\ApiClient;
use Mollsoft\Telegram\Builder\EditMessageText;
use Mollsoft\Telegram\Builder\SendMessage;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\DTO\Message\Document;
use Mollsoft\Telegram\DTO\Message\Photo;
use Mollsoft\Telegram\DTO\Message\Video;
use Mollsoft\Telegram\Enums\ChatAction;

class ChatAPI extends ApiClient
{
    public function __construct(string $token, protected readonly int|string $chatId)
    {
        $client = Http::baseUrl("https://api.telegram.org/bot$token/")
            ->connectTimeout(20)
            ->timeout(60);

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

    public function send(Message $message): Message
    {
        $data = [
            'chat_id' => $this->chatId,
            'parse_mode' => 'html',
        ];

        if ($message->replyKeyboard()) {
            $data['reply_markup'] = $message->replyKeyboard()->toArray();
        }

        if ($message->inlineKeyboard()) {
            $data['reply_markup'] = $message->inlineKeyboard()->toArray();
        }

        if ($message instanceof Photo) {
            if ($caption = $message->caption()) {
                $data['caption'] = $caption;
            }

            $src = $message->photoSrc();
            $hash = null;
            if (File::exists($src)) {
                $hash = hash_file('sha256', $src);
                $cacheSrc = Cache::get('telegram_'.$hash);
                $src = $cacheSrc ?: fopen($src, 'r');
            }

            try {
                $responseData = $this->sendRequest('sendPhoto', [
                    ...$data,
                    'photo' => $src,
                ]);
            } catch (\Exception) {
                $src = $message->photoSrc();
                if (File::exists($src)) {
                    $src = fopen($src, 'r');
                }

                $responseData = $this->sendRequest('sendPhoto', [
                    ...$data,
                    'photo' => $src,
                ]);
            }

            if ($hash && ($photo = $responseData['photo'] ?? null)) {
                Cache::set('telegram_'.$hash, $photo[count($photo) - 1]['file_id'], 86400);
            }

            $responseData['photo_src'] = $message->photoSrc();
        } elseif ($message instanceof Video) {
            if ($caption = $message->caption()) {
                $data['caption'] = $caption;
            }

            $src = $message->videoSrc();
            $hash = null;
            if (File::exists($src)) {
                $hash = hash_file('sha256', $src);
                $cacheSrc = Cache::get('telegram_'.$hash);
                $src = $cacheSrc ?: fopen($src, 'r');
            }

            try {
                $responseData = $this->sendRequest('sendVideo', [
                    ...$data,
                    'video' => $src,
                ]);
            } catch (\Exception) {
                $src = $message->videoSrc();
                if (File::exists($src)) {
                    $src = fopen($src, 'r');
                }

                $responseData = $this->sendRequest('sendVideo', [
                    ...$data,
                    'video' => $src,
                ]);
            }

            if ($hash && ($video = $responseData['video'] ?? null)) {
                Cache::set('telegram_'.$hash, $video['file_id'], 86400);
            }

            $responseData['video_src'] = $message->videoSrc();
        } elseif ($message instanceof Video) {
            if ($caption = $message->caption()) {
                $data['caption'] = $caption;
            }

            $src = $message->videoSrc();
            $hash = null;
            if (File::exists($src)) {
                $hash = hash_file('sha256', $src);
                $cacheSrc = Cache::get('telegram_'.$hash);
                $src = $cacheSrc ?: fopen($src, 'r');
            }

            try {
                $responseData = $this->sendRequest('sendVideo', [
                    ...$data,
                    'video' => $src,
                ]);
            } catch (\Exception) {
                $src = $message->videoSrc();
                if (File::exists($src)) {
                    $src = fopen($src, 'r');
                }

                $responseData = $this->sendRequest('sendVideo', [
                    ...$data,
                    'video' => $src,
                ]);
            }

            if ($hash && ($video = $responseData['video'] ?? null)) {
                Cache::set('telegram_'.$hash, $video['file_id'], 86400);
            }

            $responseData['video_src'] = $message->videoSrc();
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
                $responseData = $this->sendRequest('sendDocument', [
                    ...$data,
                    'document' => $src,
                ]);
            } catch (\Exception) {
                $src = $message->documentSrc();
                if (File::exists($src)) {
                    $src = fopen($src, 'r');
                }

                $responseData = $this->sendRequest('sendDocument', [
                    ...$data,
                    'document' => $src,
                ]);editMessageMedia
            }

            if ($hash && ($document = $responseData['document'] ?? null)) {
                Cache::set('telegram_'.$hash, $document['file_id'], 86400);
            }

            $responseData['document_src'] = $message->documentSrc();
        } else {
            if ($message->text()) {
                $data['text'] = $message->text();
            }

            $responseData = $this->sendRequest('sendMessage', $data);
        }

        if (isset($data['reply_markup'])) {
            $responseData['reply_markup'] = $data['reply_markup'];
        }

        return Message::fromArray($responseData);
    }

    public function canEdit(Message $old, Message $new): bool
    {
        if (!$old->from()->isBot()) {
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

        if ($old->caption() && $new->caption()) {
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

                $responseData = $this->sendRequest('editMessageMedia', [
                    'chat_id' => $this->chatId,
                    'message_id' => $old->id(),
                    'media' => [
                        'type' => 'photo',
                        'media' => $src,
                        'caption' => $new->caption(),
                        'parse_mode' => 'html',
                    ],
                    'reply_markup' => $new->inlineKeyboard()?->toArray() ?? ['inline_keyboard' => []]
                ]);

                if ($hash && ($photo = $responseData['photo'] ?? null)) {
                    Cache::set('telegram_'.$hash, $photo[count($photo) - 1]['file_id'], 86400);
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

                $responseData = $this->sendRequest('editMessageMedia', [
                    'chat_id' => $this->chatId,
                    'message_id' => $old->id(),
                    'media' => [
                        'type' => 'video',
                        'media' => $src,
                        'caption' => $new->caption(),
                        'parse_mode' => 'html',
                    ],
                    'reply_markup' => $new->inlineKeyboard()?->toArray() ?? ['inline_keyboard' => []]
                ]);

                if ($hash && ($video = $responseData['video'] ?? null)) {
                    Cache::set('telegram_'.$hash, $video['file_id'], 86400);
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

            $responseData['video_src'] = $new->videoSrc();
        } elseif ($old instanceof Document && $new instanceof Document) {
            if ($old->documentSrc() !== $new->documentSrc()) {
                $src = $new->documentSrc();
                $hash = null;
                if (File::exists($src)) {
                    $hash = hash_file('sha256', $src);
                    $cacheSrc = Cache::get('telegram_'.$hash);
                    $src = $cacheSrc ?: fopen($src, 'r');
                }

                $responseData = $this->sendRequest('editMessageMedia', [
                    'chat_id' => $this->chatId,
                    'message_id' => $old->id(),
                    'media' => [
                        'type' => 'video',
                        'media' => $src,
                        'caption' => $new->caption(),
                        'parse_mode' => 'html',
                    ],
                    'reply_markup' => $new->inlineKeyboard()?->toArray() ?? ['inline_keyboard' => []]
                ]);

                if ($hash && ($document = $responseData['document'] ?? null)) {
                    Cache::set('telegram_'.$hash, $document['file_id'], 86400);
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

    public function sendMessage(string $text): SendMessage
    {
        return new SendMessage($this, $this->chatId, $text);
    }

    public function sendChatAction(ChatAction $action): bool
    {
        return $this->sendRequest('sendChatAction', [
            'chat_id' => $this->chatId,
            'action' => $action->value,
        ])[0];
    }

    public function editMessageText(int $messageId, string $text): EditMessageText
    {
        return new EditMessageText($this, $this->chatId, $messageId, $text);
    }

    public function try(string $method, ...$arguments): mixed
    {
        try {
            return call_user_func_array([$this, $method], $arguments);
        } catch (\Exception) {
            return false;
        }
    }
}
