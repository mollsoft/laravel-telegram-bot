<?php

namespace Mollsoft\Telegram\Foundation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;

/**
 * @template T
 * @extends Collection<int, T>
 */
class RedisCollection extends Collection
{
    protected $getter, $setter;
    public function __construct(protected readonly string $redisKey, ?callable $getter = null, ?callable $setter = null)
    {
        parent::__construct();

        $this->getter = $getter ?? function(mixed $value) { return unserialize($value); };
        $this->setter = $setter ?? function(mixed $value) { return serialize($value); };

        $this->load();
    }

    public function load(): static
    {
        $this->items = Redis::lRange($this->redisKey, 0, -1);
        $this->items = array_map($this->getter, $this->items);

        return $this;
    }

    public function getIterator(): \Traversable
    {
        $this->load();

        return parent::getIterator();
    }

    public function all(): array
    {
        $this->load();

        return parent::all();
    }

    public function push(...$values): static
    {
        foreach ($values as $value) {
            Redis::rPush($this->redisKey, call_user_func($this->setter, $value));
        }

        return $this;
    }

    public function unshift(...$values): static
    {
        foreach ($values as $value) {
            Redis::lPush($this->redisKey, call_user_func($this->setter, $value));
        }

        return $this;
    }

    public function count(): int
    {
        return Redis::lLen($this->redisKey);
    }

    public function get($key, $default = null): mixed
    {
        $value = Redis::lIndex($this->redisKey, $key);
        if( $value ) {
            return call_user_func($this->getter, $value);
        }

        return $default;
    }

    public function put($key, $value): static
    {
        Redis::lSet($this->redisKey, $key, call_user_func($this->setter, $value));

        return $this;
    }

    public function truncate(): static
    {
        Redis::del($this->redisKey);

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function last(?callable $callback = null, $default = null)
    {
        $this->load();

        return parent::last($callback, $default);
    }

    public function first(?callable $callback = null, $default = null)
    {
        $this->load();

        return parent::first($callback, $default);
    }

    public function pop($count = 1): mixed
    {
        if( $count === 1 ) {
            $value = Redis::rPop($this->redisKey);
            return $value === false ? null : call_user_func($this->getter, $value);
        }

        $collectionCount = $this->count();
        if( $collectionCount === 0 ) {
            return new Collection();
        }

        $results = [];

        foreach (range(1, min($count, $collectionCount)) as $item) {
            $results[] = $this->pop();
        }

        return new Collection($results);
    }

    public function shift($count = 1): mixed
    {
        if( $count === 1 ) {
            $value = Redis::lPop($this->redisKey);
            return $value === false ? null : call_user_func($this->getter, $value);
        }

        $collectionCount = $this->count();
        if( $collectionCount === 0 ) {
            return new Collection();
        }

        $results = [];

        foreach (range(1, min($count, $collectionCount)) as $item) {
            $results[] = $this->pop();
        }

        return new Collection($results);
    }

    public function forget($keys): static
    {
        $keys = $this->getArrayableItems($keys);

        $list = Redis::lrange($this->redisKey, 0, -1);
        foreach ($keys as $index) {
            if (isset($list[$index])) {
                $list[$index] = null;
            }
        }
        $newList = array_filter($list, function($element) {
            return $element !== null;
        });
        Redis::del($this->redisKey);
        foreach ($newList as $element) {
            Redis::rpush($this->redisKey, $element);
        }

        return $this;
    }
}
