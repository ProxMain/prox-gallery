<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Models;

use Prox\ProxGallery\Storage\AtomicOptionStore;
use Prox\ProxGallery\Contracts\ModelInterface;
use Prox\ProxGallery\Modules\MediaLibrary\DTO\TrackedImageDto;

/**
 * Stores tracked uploaded image DTO payloads for gallery overview use.
 */
final class UploadedImageQueueModel implements ModelInterface
{
    public function __construct(private AtomicOptionStore $store = new AtomicOptionStore())
    {
    }

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
        $this->store->mutate($this->optionKey(), function (array &$raw) use ($image): void {
            $itemsById = [];
            foreach ($this->normalizeRows($raw) as $item) {
                $itemsById[$item->id] = $item->toArray();
            }
            $itemsById[$image->id] = $image->toArray();
            $raw = array_values($itemsById);
        });
    }

    /**
     * @param list<TrackedImageDto> $items
     */
    public function replaceAll(array $items): void
    {
        $this->store->mutate($this->optionKey(), static function (array &$raw) use ($items): void {
            $raw = array_map(static fn (TrackedImageDto $item): array => $item->toArray(), array_values($items));
        });
    }

    /** @return array{removed:int,remaining:int} Counts from the committed state. */
    public function removeIds(array $ids): array
    {
        return $this->store->mutate($this->optionKey(), function (array &$raw) use ($ids): array {
            $items = $this->normalizeRows($raw);
            $raw = array_values(array_map(
                static fn (TrackedImageDto $item): array => $item->toArray(),
                array_filter($items, static fn (TrackedImageDto $item): bool => ! in_array($item->id, $ids, true))
            ));
            return ['removed' => count($items) - count($raw), 'remaining' => count($raw)];
        });
    }

    /**
     * @return list<TrackedImageDto>
     */
    public function all(): array
    {
        return $this->normalizeRows($this->store->read($this->optionKey()));
    }

    private function normalizeRows(array $value): array
    {


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
