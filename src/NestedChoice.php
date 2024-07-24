<?php

namespace Mollsoft\Telegram;

use Illuminate\Support\Collection;

class NestedChoice
{
    protected Collection $levels, $selected;
    protected mixed $item = null;
    protected bool $isEnd;

    protected readonly Collection $dataMap;
    protected ?array $valuePath = null;

    public function __construct(
        protected readonly array $data,
        protected ?string $value = null,
        protected readonly bool $isKeyUnique = false,
        protected readonly string $separator = '-'
    ) {
        $this->dataMap = collect();
        $this
            ->mapData($this->data)
            ->setValue($this->value);
    }

    protected function mapData(array $items, array $path = []): static
    {
        foreach ($items as $key => $item) {
            $currentPath = array_merge($path, [$key]);
            $currentKey = $this->isKeyUnique ? $key : implode('-', $currentPath);
            $this->dataMap->put($currentKey, $currentPath);

            if (isset($item['children'])) {
                $this->mapData($item['children'], $currentPath);
            }
        }

        return $this;
    }

    public function setValue(mixed $value): static
    {
        $this->value = !is_null($value) && $value !== '' ? (string)$value : null;
        if( $this->value !== null && !$this->dataMap->has($this->value) ) {
            $this->value = null;
        }
        $this->valuePath = $this->value !== null ? $this->dataMap->get($this->value) : null;

        $this
            ->initLevels()
            ->initSelected()
            ->initItem()
            ->initIsEnd();

        return $this;
    }

    protected function initLevels(): static
    {
        $this->levels = collect();

        $data = $this->data;

        $items = collect();
        foreach ($data as $i => $item) {
            $items->put($i, $item['item']);
        }
        $this->levels->push($items);

        if ($this->valuePath) {
            $prefix = '';
            foreach( $this->valuePath as $path ) {
                $prefix .= $path.'-';
                if( !isset($data[$path]['children']) ) {
                    break;
                }
                $data = $data[$path]['children'];

                $items = collect();
                foreach ($data as $i => $item) {
                    $items->put($this->isKeyUnique ? $i : $prefix.$i, $item['item']);
                }
                $this->levels->push($items);
            }
        }

        return $this;
    }

    protected function initSelected(): static
    {
        $this->selected = collect();

        if ($this->valuePath) {
            $data = $this->data;

            foreach ($this->valuePath as $path) {
                if (!isset($data[$path])) {
                    break;
                }

                $this->selected->push($data[$path]['item']);
                $data = $data[$path]['children'] ?? [];
            }
        }

        return $this;
    }

    protected function initItem(): static
    {
        $this->item = null;

        $data = $this->data;

        foreach ($this->valuePath ?? [] as $path) {
            if (isset($data['children'])) {
                $data = $data['children'];
            }

            if (!isset($data[$path])) {
                return $this;
            }

            $data = $data[$path];
        }

        $this->item = $data['item'] ?? null;

        return $this;
    }

    protected function initIsEnd(): static
    {
        $this->isEnd = false;

        $data = $this->data;

        foreach ($this->valuePath ?? [] as $path) {
            if (!isset($data[$path])) {
                $this->isEnd = true;
                return $this;
            }

            $data = $data[$path]['children'] ?? $data[$path];
        }

        if (!isset($data['item']) || isset($data['children'])) {
            return $this;
        }

        $this->isEnd = true;

        return $this;
    }

    public static function fromTree(Collection $tree, ?string $value = null, ?string $uniqueKeyBy = null): static
    {
        $data = static::formatedTree($tree, $uniqueKeyBy);

        return new static($data, $value, $uniqueKeyBy !== null);
    }

    protected static function formatedTree(Collection $collection, ?string $uniqueKeyBy = null): array
    {
        $result = [];

        foreach ($collection as $i => $item) {
            $key = $i;
            if ($uniqueKeyBy) {
                $key = $item->$uniqueKeyBy;
            }

            $result[$key] = [
                'item' => $item,
            ];

            if ($item->children->count()) {
                $result[$key]['children'] = static::formatedTree($item->children, $uniqueKeyBy);
            }
        }

        return $result;
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function levels(): Collection
    {
        return $this->levels;
    }

    public function selected(?int $level = null): Collection
    {
        $collection = collect();

        $data = $this->data;

        foreach ($this->valuePath ?? [] as $i => $path) {
            if ($level !== null && $i >= $level) {
                break;
            }
            if (!isset($data[$path])) {
                break;
            }

            $collection->push($data[$path]['item']);
            $data = $data[$path]['children'] ?? [];
        }

        return $collection;
    }

    public function isSelected(mixed $value): bool
    {
        return $this->selected()->filter(fn(mixed $item) => $item === $value)->count() > 0;
    }

    public function isEnd(): bool
    {
        return $this->isEnd;
    }

    public function item(mixed $default = null): mixed
    {
        return $this->item ?? $default;
    }

    public function backValue(): ?string
    {
        $values = $this->valuePath ?? [];

        if (count($values) > 0) {
            unset($values[count($values) - 1]);
        }

        if( empty($values) ) {
            return null;
        }

        if( $this->isKeyUnique ) {
            return end($values);
        }

        return implode($this->separator, $values);
    }
}
