<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Services\Contracts;

/**
 * Read abstraction for frontend gallery data.
 */
interface FrontendGalleryRepositoryInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    public function loadGalleries(int $galleryId): array;
}
