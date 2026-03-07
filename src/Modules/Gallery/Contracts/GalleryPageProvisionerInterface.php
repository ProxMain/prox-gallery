<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Gallery\Contracts;

/**
 * Provisions frontend pages for galleries.
 */
interface GalleryPageProvisionerInterface
{
    /**
     * @param array{id:int, name:string, description:string, template:string, created_at:string, image_ids:list<int>} $gallery
     *
     * @return array{
     *     page_id:int,
     *     page_url:string,
     *     menu_id:int,
     *     menu_item_id:int
     * }
     */
    public function provisionForGallery(int $galleryId, array $gallery): array;
}
