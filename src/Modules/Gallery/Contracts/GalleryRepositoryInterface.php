<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Gallery\Contracts;

/**
 * Abstraction for gallery persistence operations.
 */
interface GalleryRepositoryInterface
{
    /**
     * @return list<array{
     *   id:int,
     *   name:string,
     *   description:string,
     *   template:string,
     *   grid_columns_override:int|null,
     *   lightbox_override:bool|null,
     *   hover_zoom_override:bool|null,
     *   full_width_override:bool|null,
     *   transition_override:string|null,
     *   show_title:bool,
     *   show_description:bool,
     *   created_at:string,
     *   image_ids:list<int>
     * }>
     */
    public function all(): array;

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   description:string,
     *   template:string,
     *   grid_columns_override:int|null,
     *   lightbox_override:bool|null,
     *   hover_zoom_override:bool|null,
     *   full_width_override:bool|null,
     *   transition_override:string|null,
     *   show_title:bool,
     *   show_description:bool,
     *   created_at:string,
     *   image_ids:list<int>
     * }
     */
    public function create(
        string $name,
        string $description = '',
        string $template = 'basic-grid',
        ?int $gridColumnsOverride = null,
        ?bool $lightboxOverride = null,
        ?bool $hoverZoomOverride = null,
        ?bool $fullWidthOverride = null,
        ?string $transitionOverride = null,
        bool $showTitle = true,
        bool $showDescription = true
    ): array;

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   description:string,
     *   template:string,
     *   grid_columns_override:int|null,
     *   lightbox_override:bool|null,
     *   hover_zoom_override:bool|null,
     *   full_width_override:bool|null,
     *   transition_override:string|null,
     *   show_title:bool,
     *   show_description:bool,
     *   created_at:string,
     *   image_ids:list<int>
     * }|null
     */
    public function find(int $id): ?array;

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   description:string,
     *   template:string,
     *   grid_columns_override:int|null,
     *   lightbox_override:bool|null,
     *   hover_zoom_override:bool|null,
     *   full_width_override:bool|null,
     *   transition_override:string|null,
     *   show_title:bool,
     *   show_description:bool,
     *   created_at:string,
     *   image_ids:list<int>
     * }|null
     */
    public function rename(
        int $id,
        string $name,
        string $description,
        ?string $template = null,
        ?int $gridColumnsOverride = null,
        ?bool $lightboxOverride = null,
        ?bool $hoverZoomOverride = null,
        ?bool $fullWidthOverride = null,
        ?string $transitionOverride = null,
        ?bool $showTitle = null,
        ?bool $showDescription = null
    ): ?array;

    public function delete(int $id): bool;

    /**
     * @return list<int>
     */
    public function galleryIdsForImage(int $imageId): array;

    /**
     * @param list<int> $galleryIds
     *
     * @return list<int>
     */
    public function setImageGalleries(int $imageId, array $galleryIds): array;

    /**
     * @param list<int> $imageIds
     */
    public function addImagesToGallery(int $galleryId, array $imageIds): bool;

    /**
     * @param list<int> $imageIds
     */
    public function setGalleryImages(int $galleryId, array $imageIds): bool;
}
