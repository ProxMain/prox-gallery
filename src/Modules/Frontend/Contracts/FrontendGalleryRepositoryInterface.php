<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Frontend\Contracts;

/**
 * Read abstraction for frontend gallery data.
 */
interface FrontendGalleryRepositoryInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    public function loadGalleries(int $galleryId): array;

    public function exists(int $galleryId): bool;

    public function galleryContainsImage(int $galleryId, int $imageId): bool;
}
