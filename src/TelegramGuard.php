<?php

namespace Mollsoft\Telegram;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Traits\Macroable;
use Mollsoft\Telegram\Facades\Telegram;
use Mollsoft\Telegram\Models\TelegramBot;
use Mollsoft\Telegram\Models\TelegramUser;

class TelegramGuard implements StatefulGuard
{
    use GuardHelpers, Macroable;

    protected bool $loggedOut = false;

    public function __construct(
        UserProvider $provider,
        protected TelegramRequest $request
    ) {
        $this->provider = $provider;
    }

    public function user(): ?Authenticatable
    {
        if ($this->loggedOut) {
            return null;
        }

        if (!is_null($this->user)) {
            return $this->user;
        }

        /** @var class-string<TelegramUser> $model */
        $model = Telegram::userModel();

        $this->user = $model::query()
            ->with('authenticatable')
            ->whereTelegramChatId($this->request->chat()->chat_id)
            ->first()
            ?->authenticatable;

        return $this->user;
    }

    public function validate(array $credentials = []): bool
    {
        if ($this->provider->retrieveByCredentials($credentials)) {
            return true;
        }

        return false;
    }

    public function attempt(array $credentials = [], $remember = false): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            $this->login($user, $remember);

            return true;
        }

        return false;
    }

    public function once(array $credentials = []): bool
    {
        return $this->attempt($credentials);
    }

    public function login(Authenticatable $user, $remember = false): void
    {
        /** @var class-string<TelegramUser> $model */
        $model = Telegram::userModel();

        $model::updateOrCreate([
            'telegram_chat_id' => $this->request->chat()->chat_id
        ], [
            'authenticatable_type' => get_class($user),
            'authenticatable_id' => $user->getAuthIdentifier()
        ]);

        $this->user = $user;
    }

    public function loginUsingId($id, $remember = false): ?Authenticatable
    {
        if (!is_null($user = $this->provider->retrieveById($id))) {
            $this->login($user, $remember);

            return $user;
        }

        return null;
    }

    public function onceUsingId($id): ?Authenticatable
    {
        return $this->loginUsingId($id);
    }

    public function viaRemember(): bool
    {
        return true;
    }

    public function logout(): void
    {
        /** @var class-string<TelegramUser> $model */
        $model = Telegram::userModel();

        $model::query()
            ->whereTelegramChatId($this->request->chat()->chat_id)
            ->delete();

        $this->user = null;
        $this->loggedOut = true;
    }

    protected function hasValidCredentials($user, $credentials): bool
    {
        return !is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }
}
