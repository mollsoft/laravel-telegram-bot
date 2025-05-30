<?php

namespace Mollsoft\Telegram\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mollsoft\Telegram\DTO\InlineKeyboard;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\DTO\Message\Document;
use Mollsoft\Telegram\DTO\Message\Photo;
use Mollsoft\Telegram\DTO\Message\Video;
use Mollsoft\Telegram\DTO\Message\VideoNote;
use Mollsoft\Telegram\DTO\Message\Voice;
use Mollsoft\Telegram\DTO\ReplyKeyboard;
use Mollsoft\Telegram\DTO\ReplyParameters;
use Mollsoft\Telegram\DTO\VoiceNote;
use Symfony\Component\DomCrawler\Crawler;


readonly class HTMLParser
{
    protected Crawler $crawler;
    /** @var Collection<Message> */
    public Collection $screenMessages;
    /** @var Collection<Message> */
    public Collection $appendMessages;

    public function __construct(string $html)
    {
        $this->crawler = (new Crawler('<root>'.$html.'</root>'))->filter('root');
        $this->screenMessages = collect();
        $this->appendMessages = collect();

        $this->parseMessages();
    }

    public function type(): string
    {
        $isScreen = $this->screenMessages->count() > 0;
        $isMixed = $this->appendMessages->count() > 0;

        return $isScreen ? ($isMixed ? 'mixed' : 'screen') : 'classic';
    }

    protected function parseMessages(): static
    {
        $this->crawler
            ->filter('message, photo, video, document, voice, video-note')
            ->each(fn(Crawler $item) => match ($item->nodeName()) {
                'message' => $this->createMessage($item),
                'photo' => $this->createPhoto($item),
                'video' => $this->createVideo($item),
                'document' => $this->createDocument($item),
                'voice' => $this->createVoice($item),
                'video-note' => $this->createVideoNote($item),
            });

        return $this;
    }

    protected function createMessage(Crawler $crawler): Message
    {
        $message = Message::make();

        if ($replyMessageId = $crawler->attr('reply-message-id')) {
            $message->setReplyParameters(
                ReplyParameters::fromArray([
                    'message_id' => $replyMessageId,
                ])
            );
        }

        $lines = [];

        $crawler
            ->children()
            ->each(function (Crawler $crawler) use (&$lines, $message) {
                switch ($crawler->nodeName()) {
                    case 'lines':
                        $lines[] = $crawler->html();
                        break;

                    case 'line':
                        $lines[] = trim(
                            str_replace("\n", '', $crawler->html())
                        );
                        break;

                    case 'reply-keyboard':
                        $message->setReplyKeyboard(
                            $this->replyKeyboard($crawler)
                        );
                        break;

                    case 'inline-keyboard':
                        $message->setInlineKeyboard(
                            $this->inlineKeyboard($crawler)
                        );
                        break;
                }
            });

        if (count($lines) > 0) {
            $linesString = implode("\n", $lines);
            if ($linesString) {
                $message->setText($linesString);
            }
        }

        $isScreen = $crawler->closest('screen');
        if ($isScreen) {
            $this->screenMessages->push($message);
        } else {
            $this->appendMessages->push($message);
        }

        return $message;
    }

    protected function createPhoto(Crawler $crawler): Photo
    {
        $photo = Photo::make();

        if ($replyMessageId = $crawler->attr('reply-message-id')) {
            $photo->setReplyParameters(
                ReplyParameters::fromArray([
                    'message_id' => $replyMessageId,
                ])
            );
        }

        if ($src = $crawler->attr('src')) {
            $photo->setPhotoSrc($src);
        }

        if( $show_caption_above_media = $crawler->attr('show_caption_above_media') ) {
            $photo->setShowCaptionAboveMedia(
                boolval($show_caption_above_media)
            );
        }

        $lines = [];

        $crawler
            ->children()
            ->each(function (Crawler $crawler) use (&$lines, $photo) {
                switch ($crawler->nodeName()) {
                    case 'lines':
                        $lines[] = $crawler->html();
                        break;

                    case 'line':
                        $lines[] = trim(
                            str_replace("\n", '', $crawler->html())
                        );
                        break;

                    case 'reply-keyboard':
                        $photo->setReplyKeyboard(
                            $this->replyKeyboard($crawler)
                        );
                        break;

                    case 'inline-keyboard':
                        $photo->setInlineKeyboard(
                            $this->inlineKeyboard($crawler)
                        );
                        break;
                }
            });

        if (count($lines) > 0) {
            $linesString = implode("\n", $lines);
            if ($linesString) {
                $photo->setCaption($linesString);
            }
        }

        $isScreen = $crawler->closest('screen');
        if ($isScreen) {
            $this->screenMessages->push($photo);
        } else {
            $this->appendMessages->push($photo);
        }

        return $photo;
    }

    protected function createVideo(Crawler $crawler): Video
    {
        $video = Video::make();

        if ($replyMessageId = $crawler->attr('reply-message-id')) {
            $video->setReplyParameters(
                ReplyParameters::fromArray([
                    'message_id' => $replyMessageId,
                ])
            );
        }

        if ($src = $crawler->attr('src')) {
            $video->setVideoSrc($src);
        }

        if( $show_caption_above_media = $crawler->attr('show_caption_above_media') ) {
            $video->setShowCaptionAboveMedia(
                boolval($show_caption_above_media)
            );
        }

        $lines = [];

        $crawler
            ->children()
            ->each(function (Crawler $crawler) use (&$lines, $video) {
                switch ($crawler->nodeName()) {
                    case 'lines':
                        $lines[] = $crawler->html();
                        break;

                    case 'line':
                        $lines[] = trim(
                            str_replace("\n", '', $crawler->html())
                        );
                        break;

                    case 'reply-keyboard':
                        $video->setReplyKeyboard(
                            $this->replyKeyboard($crawler)
                        );
                        break;

                    case 'inline-keyboard':
                        $video->setInlineKeyboard(
                            $this->inlineKeyboard($crawler)
                        );
                        break;
                }
            });

        if (count($lines) > 0) {
            $linesString = implode("\n", $lines);
            if ($linesString) {
                $video->setCaption($linesString);
            }
        }

        $isScreen = $crawler->closest('screen');
        if ($isScreen) {
            $this->screenMessages->push($video);
        } else {
            $this->appendMessages->push($video);
        }

        return $video;
    }

    protected function createVideoNote(Crawler $crawler): VideoNote
    {
        $entity = VideoNote::make();

        if ($replyMessageId = $crawler->attr('reply-message-id')) {
            $entity->setReplyParameters(
                ReplyParameters::fromArray([
                    'message_id' => $replyMessageId,
                ])
            );
        }

        if ($src = $crawler->attr('src')) {
            $entity->setVideoNoteSrc($src);
        }

        $crawler
            ->children()
            ->each(function (Crawler $crawler) use (&$lines, $entity) {
                switch ($crawler->nodeName()) {
                    case 'reply-keyboard':
                        $entity->setReplyKeyboard(
                            $this->replyKeyboard($crawler)
                        );
                        break;

                    case 'inline-keyboard':
                        $entity->setInlineKeyboard(
                            $this->inlineKeyboard($crawler)
                        );
                        break;
                }
            });

        $isScreen = $crawler->closest('screen');
        if ($isScreen) {
            $this->screenMessages->push($entity);
        } else {
            $this->appendMessages->push($entity);
        }

        return $entity;
    }

    protected function createVoice(Crawler $crawler): Voice
    {
        $voice = Voice::make();

        if ($replyMessageId = $crawler->attr('reply-message-id')) {
            $message->setReplyParameters(
                ReplyParameters::fromArray([
                    'message_id' => $replyMessageId,
                ])
            );
        }

        if ($src = $crawler->attr('src')) {
            $voice->setVoiceSrc($src);
        }

        $lines = [];

        $crawler
            ->children()
            ->each(function (Crawler $crawler) use (&$lines, $voice) {
                switch ($crawler->nodeName()) {
                    case 'lines':
                        $lines[] = $crawler->html();
                        break;

                    case 'line':
                        $lines[] = trim(
                            str_replace("\n", '', $crawler->html())
                        );
                        break;

                    case 'reply-keyboard':
                        $voice->setReplyKeyboard(
                            $this->replyKeyboard($crawler)
                        );
                        break;

                    case 'inline-keyboard':
                        $voice->setInlineKeyboard(
                            $this->inlineKeyboard($crawler)
                        );
                        break;
                }
            });

        if (count($lines) > 0) {
            $linesString = implode("\n", $lines);
            if ($linesString) {
                $voice->setCaption($linesString);
            }
        }

        $isScreen = $crawler->closest('screen');
        if ($isScreen) {
            $this->screenMessages->push($voice);
        } else {
            $this->appendMessages->push($voice);
        }

        return $voice;
    }

    protected function createDocument(Crawler $crawler): Document
    {
        $document = Document::make();

        if ($replyMessageId = $crawler->attr('reply-message-id')) {
            $message->setReplyParameters(
                ReplyParameters::fromArray([
                    'message_id' => $replyMessageId,
                ])
            );
        }

        if ($src = $crawler->attr('src')) {
            $document->setDocumentSrc($src);
        }

        $lines = [];

        $crawler
            ->children()
            ->each(function (Crawler $crawler) use (&$lines, $document) {
                switch ($crawler->nodeName()) {
                    case 'lines':
                        $lines[] = $crawler->html();
                        break;

                    case 'line':
                        $lines[] = trim(
                            str_replace("\n", '', $crawler->html())
                        );
                        break;

                    case 'reply-keyboard':
                        $document->setReplyKeyboard(
                            $this->replyKeyboard($crawler)
                        );
                        break;

                    case 'inline-keyboard':
                        $document->setInlineKeyboard(
                            $this->inlineKeyboard($crawler)
                        );
                        break;
                }
            });

        if (count($lines) > 0) {
            $linesString = implode("\n", $lines);
            if ($linesString) {
                $document->setCaption($linesString);
            }
        }

        $isScreen = $crawler->closest('screen');
        if ($isScreen) {
            $this->screenMessages->push($document);
        } else {
            $this->appendMessages->push($document);
        }

        return $document;
    }

    protected function replyKeyboard(Crawler $crawler): ?ReplyKeyboard
    {
        $replyKeyboard = ReplyKeyboard::make();

        $replyKeyboard->setResize(!!$crawler->attr('resize', true));
        $replyKeyboard->setIsPersistent(!!$crawler->attr('persistent', true));

        $crawler
            ->filter('row')
            ->each(function (Crawler $crawler, int $rowIndex) use ($replyKeyboard) {
                $crawler
                    ->filter('column')
                    ->each(function (Crawler $crawler) use ($rowIndex, $replyKeyboard) {
                        $button = ReplyKeyboard\Button::make()->setText(
                            trim(
                                str_replace("\n", '', $crawler->html())
                            )
                        );
                        if ($crawler->attr('request_contact', 'false') === 'true') {
                            $button->setRequestContact(true);
                        }
                        if ($crawler->attr('request_location', 'false') === 'true') {
                            $button->setRequestLocation(true);
                        }
                        if ($webApp = $crawler->attr('web_app', '')) {
                            $button->setWebApp(['url' => $webApp]);
                        }
                        $replyKeyboard->button(
                            $button,
                            $rowIndex
                        );
                    });
            });

        return !$replyKeyboard->isEmpty() ? $replyKeyboard : null;
    }

    protected function inlineKeyboard(Crawler $crawler): ?InlineKeyboard
    {
        $inlineKeyboard = InlineKeyboard::make();

        $crawler
            ->filter('row')
            ->each(function (Crawler $crawler, int $rowIndex) use ($inlineKeyboard) {
                $crawler
                    ->filter('column')
                    ->each(function (Crawler $crawler) use ($rowIndex, $inlineKeyboard) {
                        $callbackData = [];

                        foreach ($crawler->getNode(0)->attributes as $attribute) {
                            if (mb_strpos($attribute->nodeName, 'data-') === 0) {
                                $callbackData[mb_substr($attribute->nodeName, 5)] = $attribute->nodeValue;
                            } elseif (mb_strpos($attribute->nodeName, 'query-') === 0) {
                                $callbackData[$attribute->nodeName] = $attribute->nodeValue;
                            }
                        }

                        if (count($callbackData) === 0) {
                            $callbackData = ['default' => true];
                        }

                        if ($crawler->attr('encode', isset($callbackData['redirect']))) {
                            $data = http_build_query($callbackData);
                            $encodeId = md5($data);
                            Cache::set('telegram_'.$encodeId, $data, (int)config('telegram.cache.encode_ttl', 3600));
                            $callbackData = ['encode' => $encodeId,];
                        }

                        if ($url = $crawler->attr('url')) {
                            $inlineKeyboard->button(
                                InlineKeyboard\Button::make()
                                    ->setText(
                                        trim(
                                            str_replace("\n", '', $crawler->html())
                                        )
                                    )
                                    ->setUrl($url),
                                $rowIndex
                            );
                        } elseif ($url = $crawler->attr('web_app')) {
                            $inlineKeyboard->button(
                                InlineKeyboard\Button::make()
                                    ->setText(
                                        trim(
                                            str_replace("\n", '', $crawler->html())
                                        )
                                    )
                                    ->setWebApp(['url' => $url]),
                                $rowIndex
                            );
                        } else {
                            $inlineKeyboard->button(
                                InlineKeyboard\Button::make()
                                    ->setText(
                                        trim(
                                            str_replace("\n", '', $crawler->html())
                                        )
                                    )
                                    ->setCallbackData($callbackData),
                                $rowIndex
                            );
                        }
                    });
            });

        return !$inlineKeyboard->isEmpty() ? $inlineKeyboard : null;
    }
}
