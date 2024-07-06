<?php

namespace Mollsoft\Telegram;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Mollsoft\Telegram\Abstract\StorageDriver;

class Storage extends StorageDriver
{
    private string $key;
    private Repository $cache;

    public function __construct(string $key)
    {
        $this->key = Str::of('storage')
            ->append("_", $key)
            ->toString();

        $this->cache = Cache::store();
    }

    public function storeData(string $key, mixed $value): void
    {
        if (!Str::of($key)->contains('.')) {
            $this->cache->set("{$this->key}_$key", $value);

            return;
        }

        $mainKey = (string)Str::of($key)->before('.');
        $mainValue = $this->retrieveData($mainKey, []);

        $otherKeys = (string)Str::of($key)->after('.');
        data_set($mainValue, $otherKeys, $value);

        $this->cache->set("{$this->key}_".$mainKey, $mainValue);
    }

    public function retrieveData(string $key, mixed $default = null): mixed
    {
        if (!Str::of($key)->contains('.')) {
            return $this->cache->get("{$this->key}_$key", $default);
        }

        $mainKey = (string)Str::of($key)->before('.');
        $mainValue = $this->retrieveData($mainKey, []);

        $otherKeys = (string)Str::of($key)->after('.');

        return data_get($mainValue, $otherKeys, $default);
    }

    public function forget(string $key): static
    {
        if (!Str::of($key)->contains('.')) {
            $this->cache->forget("{$this->key}_$key");
        }

        $mainKey = (string)Str::of($key)->before('.');
        $mainValue = $this->retrieveData($mainKey, []);

        $otherKeys = (string)Str::of($key)->after('.');

        Arr::forget($mainValue, $otherKeys);

        $this->storeData($mainKey, $mainValue);

        return $this;
    }
}
