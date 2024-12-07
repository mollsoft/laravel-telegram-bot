<?php

namespace Mollsoft\Telegram\Services;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mollsoft\Telegram\ChatAPI;
use Mollsoft\Telegram\DTO\CallbackQuery;
use Mollsoft\Telegram\DTO\Chat;
use Mollsoft\Telegram\DTO\Contact;
use Mollsoft\Telegram\DTO\Document;
use Mollsoft\Telegram\DTO\Message;
use Mollsoft\Telegram\DTO\PhotoSize;
use Mollsoft\Telegram\DTO\Update;
use Mollsoft\Telegram\Enums\ChatAction;
use Mollsoft\Telegram\Facades\Telegram;
use Mollsoft\Telegram\Interfaces\HasCaption;
use Mollsoft\Telegram\MessageStack;
use Mollsoft\Telegram\Models\TelegramBot;
use Mollsoft\Telegram\Models\TelegramChat;
use Mollsoft\Telegram\Storage;
use Mollsoft\Telegram\TelegramRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;

class WebhookHandler
{
    protected int $pageTimeout, $pageWait, $pageDelay, $pageMaxRedirects;
    protected TelegramBot $bot;
    protected ?Message $message;
    protected ?CallbackQuery $callbackQuery;
    protected TelegramChat $chat;
    protected ChatAPI $api;
    protected MessageStack $stack;
    protected Storage $storage;

    public function __construct()
    {
        $this->pageTimeout = (int)config('telegram.page.timeout', 60);
        $this->pageWait = (int)config('telegram.page.wait', 0);
        $this->pageDelay = (int)config('telegram.page.delay', 0);
        $this->pageMaxRedirects = (int)config('telegram.page.max_redirects', 3);
    }

    public function handle(Request $request, TelegramBot $bot): void
    {
        $this->bot = $bot;

        $postData = $request->post();
        $update = Update::fromArray($postData);
        $this->message = $update->message();
        $this->callbackQuery = $update->callbackQuery();

        $this
            ->setupChat()
            ->parseCallbackQuery()
            ->answerCallbackQuery()
            ->run();
    }

    public function live(TelegramChat $chat): void
    {
        $this->chat = $chat;
        $this->bot = $chat->bot;
        $this->message = null;
        $this->callbackQuery = null;
        $this->api = new ChatAPI($this->bot->token, $chat->chat_id);
        $this->stack = new MessageStack($this->chat);
        $this->storage = new Storage(get_class($this->chat).'_'.$this->chat->getKey());

        $this->run();
    }

    protected function parseCallbackQuery(): static
    {
        if (!$this->callbackQuery) {
            return $this;
        }

        if (
            ($encodeId = $this->callbackQuery->getData('encode'))
            &&
            ($encodeData = Cache::get('telegram_'.$encodeId))
        ) {
            $this->callbackQuery->setData($encodeData);
        }

        $allData = $this->callbackQuery->getAllData();
        if (count($allData) > 0) {
            foreach ($allData as $key => $value) {
                if (mb_strpos($key, 'query-') === 0) {
                    $currentURI = $this->storage->get('uri') ?: '/';

                    $request = Request::create($currentURI);
                    $currentURI = $request->fullUrlWithQuery([mb_substr($key, 6) => $value]);
                    $this->storage->set('uri', $currentURI);

                    unset($allData[$key]);
                }
            }

            $this->callbackQuery->setData(
                http_build_query($allData)
            );
        }

        return $this;
    }

    protected function setupChat(): static
    {
        /** @var ?Chat $chat */
        $chat = $this->message?->chat() ?? $this->callbackQuery?->message()?->chat();
        if (is_null($chat)) {
            throw new \Exception('Chat not found.');
        }

        /** @var class-string<TelegramChat> $model */
        $model = Telegram::chatModel();

        $this->chat = $model::updateOrCreate([
            'bot_id' => $this->bot->id,
            'chat_id' => $chat->id(),
        ], [
            'username' => $chat->username(),
            'first_name' => $chat->firstName(),
            'last_name' => $chat->lastName(),
            'chat_data' => $chat->toArray(),
            'updated_at' => Date::now(),
        ]);

        $this->api = new ChatAPI($this->bot->token, $chat->id());
        $this->stack = new MessageStack($this->chat);
        $this->storage = new Storage(get_class($this->chat).'_'.$this->chat->getKey());

        return $this;
    }

    protected function answerCallbackQuery(): static
    {
        if ($this->callbackQuery) {
            try {
                $this->bot
                    ->api()
                    ->answerCallbackQuery($this->callbackQuery);
            } catch (\Exception $e) {
            }
        }

        return $this;
    }

    protected function run(): static
    {
        try {
            $this->lock(function () {
                $this->lockedTask();
                sleep($this->pageDelay);
            });
        } catch (\Exception $exception) {
            Log::error($exception);

            if ($this->message) {
                $this->api->try('deleteMessages', $this->message->id());
            }

            $isLockTimeout = $exception instanceof LockTimeoutException;
            if (!$isLockTimeout && view()->exists('telegram::errors.500')) {
                $html = view('telegram::errors.500', compact('exception'))->toHtml();
                $this->render($html);
            }
        }

        return $this;
    }

    protected function lock(Closure|callable $callback): mixed
    {
        $key = __CLASS__.'::'.__METHOD__.'_'.$this->chat->id;

        return Cache::lock($key, $this->pageTimeout)
            ->block($this->pageWait, $callback);
    }

    protected function lockedTask(): void
    {
        if ($this->message) {
            $this->stack->push($this->message);
        }

        $this->api->try('sendChatAction', ChatAction::Typing);

        $uri = $this->storage->get('uri') ?: '/';
        if ($this->message?->text() === '/start' || $this->callbackQuery?->hasData('start')) {
            $uri = '/';
        }

        $content = $this->routeLaunch(
            uri: $uri,
            message: $this->message,
            callbackQuery: $this->callbackQuery,
            redirects: 0
        );
        $this->render($content);
    }

    protected function routeLaunch(
        string $uri,
        ?Message $message = null,
        ?CallbackQuery $callbackQuery = null,
        int $redirects = 0
    ): ?string {
        $request = TelegramRequest::createFromTelegram(
            bot: $this->bot,
            chat: $this->chat,
            uri: $uri,
            message: $message,
            callbackQuery: $callbackQuery,
        );

        $request->headers->set(
            'user-agent',
            "Telegram Bot @{$this->bot->username}, user ".($this->chat->username ?: $this->chat->chat_id)
        );

        $referer = collect($this->chat->visits)
            ->first(fn(string $item) => $item !== $uri) ?? '/';
        $request->headers->set('referer', $referer.'#back');

        $cookies = Cache::get('cookies_'.TelegramChat::class.'_'.$this->chat->id);
        if ($cookies && ($cookies = @json_decode($cookies, true))) {
            foreach ($cookies as $key => $value) {
                $request->cookies->set($key, $value);
            }
        }

        App::instance('request', $request);
        App::instance(TelegramRequest::class, $request);

        /** @var Response $response */
        $response = Route::dispatch($request);
        event(new RequestHandled($request, $response));

        if ($this->chat->live_expire_at !== $request->liveExpireAt()) {
            $this->chat->update([
                'live_period' => $request->livePeriod(),
                'live_launch_at' => $request->liveLaunchAt(),
                'live_expire_at' => $request->liveExpireAt(),
            ]);
        }

        $cookies = [];
        foreach ($response->headers->getCookies() as $item) {
            $cookies[$item->getName()] = $item->getValue();
        }
        Cache::set('cookies_'.TelegramChat::class.'_'.$this->chat->id, json_encode($cookies), 60 * 60 * 24);

        if ($response instanceof RedirectResponse) {
            if ($redirects >= $this->pageMaxRedirects) {
                return view()->exists('telegram::errors.310') ? view('telegram::errors.310')->toHtml() : '';
            }

            $targetUri = $response->getTargetUrl();
            $isBack = mb_strpos($targetUri, '#back') !== false;
            if ($isBack) {
                $targetUri = str_replace("#back", '', $targetUri);

                $this->chat->update([
                    'visits' => collect($this->chat->visits)
                        ->skipUntil(fn($item) => $item === $targetUri)
                        ->skip(1)
                        ->all(),
                ]);
            }
            $this->storage->set('uri', $targetUri);

            return $this->routeLaunch(
                uri: $targetUri,
                message: null,
                callbackQuery: null,
                redirects: $redirects + 1
            );
        }

        if (!$response->isSuccessful() && view()->exists('telegram::errors.500')) {
            return view('telegram::errors.500')->toHtml();
        }

        $visits = collect($this->chat->visits);
        if ($visits->first() !== $uri) {
            $this->chat->update([
                'visits' => $visits
                    ->prepend($uri)
                    ->take(10)
                    ->all(),
            ]);
        }

        return $response->getContent();
    }

    protected function render(string $html): void
    {
        $parser = new HTMLParser(
            html: $html
        );
        $render = new TelegramRender(
            api: $this->api,
            stack: $this->stack,
            parser: $parser
        );
        $render->run();
    }
}
