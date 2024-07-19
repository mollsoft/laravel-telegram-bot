<?php

namespace Mollsoft\Telegram\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mollsoft\Telegram\DTO\InlineKeyboard;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\DTO\Message\Document;
use Mollsoft\Telegram\DTO\Message\Photo;
use Mollsoft\Telegram\DTO\Message\Video;
use Mollsoft\Telegram\DTO\ReplyKeyboard;
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
            ->filter('message, photo, video, document')
            ->each(fn(Crawler $item) => match ($item->nodeName()) {
                'message' => $this->createMessage($item),
                'photo' => $this->createPhoto($item),
                'video' => $this->createVideo($item),
                'document' => $this->createDocument($item),
            });

        return $this;
    }

    protected function createMessage(Crawler $crawler): Message
    {
        $message = Message::make();

        $lines = [];

        $crawler
            ->children()
            ->each(function (Crawler $crawler) use (&$lines, $message) {
                switch ($crawler->nodeName()) {
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

        if ($src = $crawler->attr('src')) {
            $photo->setPhotoSrc($src);
        }

        $lines = [];

        $crawler
            ->children()
            ->each(function (Crawler $crawler) use (&$lines, $photo) {
                switch ($crawler->nodeName()) {
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

        if ($src = $crawler->attr('src')) {
            $video->setVideoSrc($src);
        }

        $lines = [];

        $crawler
            ->children()
            ->each(function (Crawler $crawler) use (&$lines, $video) {
                switch ($crawler->nodeName()) {
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

    protected function createDocument(Crawler $crawler): Document
    {
        $document = Document::make();

        if ($src = $crawler->attr('src')) {
            $document->setDocumentSrc($src);
        }

        $lines = [];

        $crawler
            ->children()
            ->each(function (Crawler $crawler) use (&$lines, $document) {
                switch ($crawler->nodeName()) {
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
                        $replyKeyboard->button(
                            ReplyKeyboard\Button::make()->setText(
                                trim(
                                    str_replace("\n", '', $crawler->html())
                                )
                            ),
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
                            }
                        }

                        if (count($callbackData) === 0) {
                            $callbackData = ['default' => true];
                        }

                        if ($crawler->attr('encode', false)) {
                            $data = http_build_query($callbackData);
                            $encodeId = md5($data);
                            Cache::set('telegram_'.$encodeId, $data, 3600);
                            $callbackData = ['encode' => $encodeId,];
                        }

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
                    });
            });

        return !$inlineKeyboard->isEmpty() ? $inlineKeyboard : null;
    }
}
