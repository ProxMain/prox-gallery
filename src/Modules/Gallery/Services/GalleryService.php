<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Gallery\Services;

use InvalidArgumentException;
use Prox\ProxGallery\Contracts\ServiceInterface;
use Prox\ProxGallery\Modules\Gallery\Contracts\GalleryPageProvisionerInterface;
use Prox\ProxGallery\Modules\Gallery\Contracts\GalleryRepositoryInterface;

/**
 * Gallery domain service for admin actions.
 */
final class GalleryService implements ServiceInterface
{
    public function __construct(
        private GalleryRepositoryInterface $collection,
        private GalleryPageProvisionerInterface $pageProvisioning
    )
    {
    }

    public function id(): string
    {
        return 'gallery.service';
    }

    public function boot(): void
    {
        /**
         * Fires after the gallery service boots.
         */
        \do_action('prox_gallery/module/gallery/service/booted', $this);
    }

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
     *   image_ids:list<int>,
     *   image_count:int
     * }>
     */
    public function list(): array
    {
        return array_map(
            fn (array $item): array => $this->withImageCount($item),
            $this->collection->all()
        );
    }

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
     *   created_at:string
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
    ): array
    {
        $normalizedName = trim(\sanitize_text_field($name));
        $normalizedDescription = trim(\sanitize_text_field($description));
        $normalizedTemplate = trim(\sanitize_text_field($template));

        if ($normalizedName === '') {
            throw new InvalidArgumentException('Gallery name is required.');
        }

        if ($normalizedTemplate === '') {
            $normalizedTemplate = 'basic-grid';
        }

        $created = $this->collection->create(
            $normalizedName,
            $normalizedDescription,
            $normalizedTemplate,
            $gridColumnsOverride,
            $lightboxOverride,
            $hoverZoomOverride,
            $fullWidthOverride,
            $transitionOverride,
            $showTitle,
            $showDescription
        );

        return [
            ...$this->withImageCount($created),
        ];
    }

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
     *   created_at:string
     * }
     */
    public function rename(
        int $id,
        string $name,
        string $description = '',
        ?string $template = null,
        ?int $gridColumnsOverride = null,
        ?bool $lightboxOverride = null,
        ?bool $hoverZoomOverride = null,
        ?bool $fullWidthOverride = null,
        bool $applyDisplayOverrides = false,
        ?string $transitionOverride = null,
        ?bool $showTitle = null,
        ?bool $showDescription = null
    ): array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Gallery ID is required.');
        }

        $normalizedName = trim(\sanitize_text_field($name));
        $normalizedDescription = trim(\sanitize_text_field($description));
        $normalizedTemplate = null;

        if ($normalizedName === '') {
            throw new InvalidArgumentException('Gallery name is required.');
        }

        $current = $this->collection->find($id);

        if ($current === null) {
            throw new InvalidArgumentException('Gallery not found.');
        }

        if (is_string($template)) {
            $normalizedTemplate = trim(\sanitize_text_field($template));
            if ($normalizedTemplate === '') {
                $normalizedTemplate = 'basic-grid';
            }
        } else {
            $normalizedTemplate = (string) ($current['template'] ?? 'basic-grid');
        }

        $resolvedColumns = $applyDisplayOverrides
            ? $gridColumnsOverride
            : (array_key_exists('grid_columns_override', $current) && is_int($current['grid_columns_override'])
                ? $current['grid_columns_override']
                : null);
        $resolvedLightbox = $applyDisplayOverrides
            ? $lightboxOverride
            : (array_key_exists('lightbox_override', $current) && is_bool($current['lightbox_override'])
                ? $current['lightbox_override']
                : null);
        $resolvedHoverZoom = $applyDisplayOverrides
            ? $hoverZoomOverride
            : (array_key_exists('hover_zoom_override', $current) && is_bool($current['hover_zoom_override'])
                ? $current['hover_zoom_override']
                : null);
        $resolvedFullWidth = $applyDisplayOverrides
            ? $fullWidthOverride
            : (array_key_exists('full_width_override', $current) && is_bool($current['full_width_override'])
                ? $current['full_width_override']
                : null);
        $resolvedTransition = $applyDisplayOverrides
            ? $this->normalizeTransition($transitionOverride)
            : (array_key_exists('transition_override', $current) && is_string($current['transition_override'])
                ? $this->normalizeTransition($current['transition_override'])
                : null);
        $resolvedShowTitle = $applyDisplayOverrides
            ? ($showTitle ?? (array_key_exists('show_title', $current) ? (bool) $current['show_title'] : true))
            : (array_key_exists('show_title', $current) ? (bool) $current['show_title'] : true);
        $resolvedShowDescription = $applyDisplayOverrides
            ? ($showDescription ?? (array_key_exists('show_description', $current) ? (bool) $current['show_description'] : true))
            : (array_key_exists('show_description', $current) ? (bool) $current['show_description'] : true);

        $updated = $this->collection->rename(
            $id,
            $normalizedName,
            $normalizedDescription,
            $normalizedTemplate,
            $resolvedColumns,
            $resolvedLightbox,
            $resolvedHoverZoom,
            $resolvedFullWidth,
            $resolvedTransition,
            $resolvedShowTitle,
            $resolvedShowDescription
        );

        if ($updated === null) {
            throw new InvalidArgumentException('Gallery not found.');
        }

        return [
            ...$this->withImageCount($updated),
        ];
    }

    private function normalizeTransition(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));
        $allowed = ['none', 'slide', 'fade', 'explode', 'implode'];

        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        return null;
    }

    public function delete(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Gallery ID is required.');
        }

        if (! $this->collection->delete($id)) {
            throw new InvalidArgumentException('Gallery not found.');
        }
    }

    public function clearAll(): void
    {
        $this->collection->clearAll();
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

    /**
     * @return list<int>
     */
    public function listGalleryIdsForImage(int $imageId): array
    {
        if ($imageId <= 0) {
            throw new InvalidArgumentException('Image ID is required.');
        }

        return $this->collection->galleryIdsForImage($imageId);
    }

    /**
     * @param list<int> $galleryIds
     *
     * @return list<int>
     */
    public function setImageGalleries(int $imageId, array $galleryIds): array
    {
        if ($imageId <= 0) {
            throw new InvalidArgumentException('Image ID is required.');
        }

        return $this->collection->setImageGalleries($imageId, $galleryIds);
    }

    /**
     * @param list<int> $imageIds
     *
     * @return array{id:int, name:string, description:string, created_at:string, image_ids:list<int>, image_count:int}
     */
    public function addImagesToGallery(int $galleryId, array $imageIds): array
    {
        if ($galleryId <= 0) {
            throw new InvalidArgumentException('Gallery ID is required.');
        }

        if (! $this->collection->addImagesToGallery($galleryId, $imageIds)) {
            throw new InvalidArgumentException('Gallery not found.');
        }

        $item = $this->collection->find($galleryId);

        if ($item === null) {
            throw new InvalidArgumentException('Gallery not found.');
        }

        return [
            ...$this->withImageCount($item),
        ];
    }

    /**
     * @param list<int> $imageIds
     *
     * @return array{id:int, name:string, description:string, created_at:string, image_ids:list<int>, image_count:int}
     */
    public function setGalleryImages(int $galleryId, array $imageIds): array
    {
        if ($galleryId <= 0) {
            throw new InvalidArgumentException('Gallery ID is required.');
        }

        if (! $this->collection->setGalleryImages($galleryId, $imageIds)) {
            throw new InvalidArgumentException('Gallery not found.');
        }

        $item = $this->collection->find($galleryId);

        if ($item === null) {
            throw new InvalidArgumentException('Gallery not found.');
        }

        return [
            ...$this->withImageCount($item),
        ];
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    private function withImageCount(array $item): array
    {
        $imageIds = isset($item['image_ids']) && is_array($item['image_ids']) ? $item['image_ids'] : [];

        return [
            ...$item,
            'image_count' => count($imageIds),
        ];
    }

    /**
     * @return array{
     *     gallery_id:int,
     *     page_id:int,
     *     page_url:string,
     *     menu_id:int,
     *     menu_item_id:int
     * }
     */
    public function createFrontendPageAndMenuItem(int $galleryId): array
    {
        if ($galleryId <= 0) {
            throw new InvalidArgumentException('Gallery ID is required.');
        }

        $gallery = $this->collection->find($galleryId);

        if ($gallery === null) {
            throw new InvalidArgumentException('Gallery not found.');
        }

        $provisioned = $this->pageProvisioning->provisionForGallery($galleryId, $gallery);

        return [
            'gallery_id' => $galleryId,
            ...$provisioned,
        ];
    }
}
