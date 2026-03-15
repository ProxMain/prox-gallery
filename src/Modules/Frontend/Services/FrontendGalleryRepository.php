<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Frontend\Services;

use Prox\ProxGallery\Modules\Frontend\Contracts\FrontendGalleryRepositoryInterface;
use Prox\ProxGallery\Modules\Gallery\Contracts\GalleryRepositoryInterface;

/**
 * Loads normalized gallery rows used by frontend rendering.
 */
final class FrontendGalleryRepository implements FrontendGalleryRepositoryInterface
{
    public function __construct(private GalleryRepositoryInterface $collection)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function loadGalleries(int $galleryId): array
    {
        $items = $this->collection->all();

        if ($galleryId <= 0) {
            return $items;
        }

        $filtered = [];

        foreach ($items as $item) {
            if ((int) ($item['id'] ?? 0) === $galleryId) {
                $filtered[] = $item;
            }
        }

        return $filtered;
    }

    public function exists(int $galleryId): bool
    {
        if ($galleryId <= 0) {
            return false;
        }

        return $this->collection->exists($galleryId);
    }

    public function galleryContainsImage(int $galleryId, int $imageId): bool
    {
        if ($galleryId <= 0 || $imageId <= 0) {
            return false;
        }

        return $this->collection->galleryContainsImage($galleryId, $imageId);
    }
}
