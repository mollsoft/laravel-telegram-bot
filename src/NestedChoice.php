<?php

namespace Mollsoft\Telegram;

use Illuminate\Support\Collection;

class NestedChoice
{
    protected ?array $value;
    protected Collection $levels, $selected;
    protected mixed $item = null;
    protected string $separator = '-';
    protected bool $isEnd;

    public function __construct(protected readonly array $data, ?string $value = null)
    {
        $this->setValue($value);
    }

    public function setValue(mixed $value): static
    {
        $this->value = !is_null($value) && $value !== '' ? explode($this->separator, $value) : null;

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

        if ($this->value) {
            $prefix = '';
            foreach ($this->value as $value) {
                $prefix .= $value.'-';
                if (!isset($data[$value]['children'])) {
                    break;
                }
                $data = $data[$value]['children'];

                $items = collect();
                foreach ($data as $i => $item) {
                    $items->put($prefix.$i, $item['item']);
                }
                $this->levels->push($items);
            }
        }

        return $this;
    }

    protected function initSelected(): static
    {
        $this->selected = collect();

        if ($this->value) {
            $data = $this->data;

            foreach ($this->value as $value) {
                if (!isset($data[$value])) {
                    break;
                }

                $this->selected->push($data[$value]['item']);
                $data = $data[$value]['children'] ?? [];
            }
        }

        return $this;
    }

    protected function initItem(): static
    {
        $this->item = null;

        $data = $this->data;

        if ($this->value) {
            foreach ($this->value as $value) {
                if (isset($data['children'])) {
                    $data = $data['children'];
                }

                if (!isset($data[$value])) {
                    return $this;
                }

                $data = $data[$value];
            }
        }

        $this->item = $data['item'] ?? null;

        return $this;
    }

    protected function initIsEnd(): static
    {
        $this->isEnd = false;

        $data = $this->data;

        if ($this->value) {
            foreach ($this->value as $value) {
                if (!isset($data[$value])) {
                    $this->isEnd = true;
                    return $this;
                }

                $data = $data[$value]['children'] ?? $data[$value];
            }
        }

        if (!isset($data['item']) || isset($data['children'])) {
            return $this;
        }

        $this->isEnd = true;

        return $this;
    }

    public function setSeparator(string $separator): static
    {
        $this->separator = $separator;

        $this->setValue(
            $this->value()
        );

        return $this;
    }

    public static function fromTree(Collection $tree, ?string $value = null): static
    {
        $data = static::formatedTree($tree);

        return new static($data, $value);
    }

    protected static function formatedTree(Collection $collection): array
    {
        $result = [];

        foreach ($collection as $i => $item) {
            $result[$i] = [
                'item' => $item,
            ];

            if ($item->children->count()) {
                $result[$i]['children'] = static::formatedTree($item->children);
            }
        }

        return $result;
    }

    public function value(): ?string
    {
        return $this->value ? implode($this->separator, $this->value) : null;
    }

    public function levels(): Collection
    {
        return $this->levels;
    }

    public function selected(?int $level = null): Collection
    {
        $collection = collect();

        if ($this->value) {
            $data = $this->data;

            foreach ($this->value as $i => $value) {
                if ($level !== null && $i >= $level) {
                    break;
                }
                if (!isset($data[$value])) {
                    break;
                }

                $collection->push($data[$value]['item']);
                $data = $data[$value]['children'] ?? [];
            }
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
        $values = $this->value ?? [];

        if (count($values) > 0) {
            unset($values[count($values) - 1]);
        }

        return count($values) > 0 ? implode($this->separator, $values) : null;
    }
}
