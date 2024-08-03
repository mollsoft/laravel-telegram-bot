<?php

namespace Mollsoft\Telegram\DTO;

use Illuminate\Support\Collection;
use Mollsoft\Telegram\Abstract\DTO;

class UserProfilePhotos extends DTO
{
    protected function required(): array
    {
        return ['total_count', 'photos'];
    }

    public function totalCount(): int
    {
        return (int)$this->getOrFail('total_count');
    }

    /**
     * @return Collection<Collection<PhotoSize>>
     */
    public function photos(): Collection
    {
        $data = $this->getOrFail('photos');

        return collect(
            array_map(
                fn(array $items) => collect(
                    array_map(fn(array $item) => PhotoSize::fromArray($item), $items)
                ),
                $data
            )
        );
    }
}
