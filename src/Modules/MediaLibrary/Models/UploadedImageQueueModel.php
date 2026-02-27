<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Models;

use Prox\ProxGallery\Contracts\ModelInterface;
use Prox\ProxGallery\Modules\MediaLibrary\DTO\TrackedImageDto;

/**
 * Stores tracked uploaded image DTO payloads for gallery overview use.
 */
final class UploadedImageQueueModel implements ModelInterface
{
    private const MAX_ITEMS = 500;

    public function id(): string
    {
        return 'media_library.uploaded_image_queue';
    }

    public function optionKey(): string
    {
        return 'prox_gallery_uploaded_image_ids';
    }

    public function remember(TrackedImageDto $image): void
    {
        $items = $this->all();
        $itemsById = [];

        foreach ($items as $item) {
            $itemsById[$item->id] = $item;
        }

        $itemsById[$image->id] = $image;
        $items = array_values($itemsById);

        if (count($items) > self::MAX_ITEMS) {
            $items = array_slice($items, -self::MAX_ITEMS);
        }

        \update_option(
            $this->optionKey(),
            array_map(
                static fn (TrackedImageDto $item): array => $item->toArray(),
                $items
            ),
            false
        );
    }

    /**
     * @return list<TrackedImageDto>
     */
    public function all(): array
    {
        $value = \get_option($this->optionKey(), []);

        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $rawItem) {
            if (is_int($rawItem) || is_string($rawItem)) {
                $dto = TrackedImageDto::fromAttachmentId((int) $rawItem);

                if ($dto !== null) {
                    $items[$dto->id] = $dto;
                }

                continue;
            }

            if (! is_array($rawItem)) {
                continue;
            }

            $dto = TrackedImageDto::fromArray($rawItem);

            if ($dto !== null) {
                $items[$dto->id] = $dto;
            }
        }

        return array_values($items);
    }
}
