<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Gallery\Services;

use InvalidArgumentException;
use Prox\ProxGallery\Contracts\ServiceInterface;
use Prox\ProxGallery\Modules\Gallery\Models\GalleryCollectionModel;

/**
 * Gallery domain service for admin actions.
 */
final class GalleryService implements ServiceInterface
{
    public function __construct(private GalleryCollectionModel $collection)
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
     *   created_at:string,
     *   image_ids:list<int>,
     *   image_count:int
     * }>
     */
    public function list(): array
    {
        return array_map(
            static fn (array $item): array => [
                'id' => (int) $item['id'],
                'name' => (string) $item['name'],
                'description' => (string) $item['description'],
                'template' => isset($item['template']) ? (string) $item['template'] : 'basic-grid',
                'grid_columns_override' => isset($item['grid_columns_override']) ? (is_int($item['grid_columns_override']) ? $item['grid_columns_override'] : null) : null,
                'lightbox_override' => array_key_exists('lightbox_override', $item) ? (is_bool($item['lightbox_override']) ? $item['lightbox_override'] : null) : null,
                'hover_zoom_override' => array_key_exists('hover_zoom_override', $item) ? (is_bool($item['hover_zoom_override']) ? $item['hover_zoom_override'] : null) : null,
                'full_width_override' => array_key_exists('full_width_override', $item) ? (is_bool($item['full_width_override']) ? $item['full_width_override'] : null) : null,
                'transition_override' => array_key_exists('transition_override', $item) && is_string($item['transition_override'])
                    ? $item['transition_override']
                    : null,
                'created_at' => (string) $item['created_at'],
                'image_ids' => (array) $item['image_ids'],
                'image_count' => count((array) $item['image_ids']),
            ],
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
        ?string $transitionOverride = null
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
            $transitionOverride
        );

        return [
            ...$created,
            'image_count' => count($created['image_ids']),
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
        ?string $transitionOverride = null
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

        $updated = $this->collection->rename(
            $id,
            $normalizedName,
            $normalizedDescription,
            $normalizedTemplate,
            $resolvedColumns,
            $resolvedLightbox,
            $resolvedHoverZoom,
            $resolvedFullWidth,
            $resolvedTransition
        );

        if ($updated === null) {
            throw new InvalidArgumentException('Gallery not found.');
        }

        return [
            ...$updated,
            'image_count' => count($updated['image_ids']),
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
            ...$item,
            'image_count' => count($item['image_ids']),
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
            ...$item,
            'image_count' => count($item['image_ids']),
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

        $existingPageId = $this->findExistingGalleryPageId($galleryId);
        $pageId = $existingPageId > 0 ? $existingPageId : $this->createGalleryPage($galleryId, $gallery);

        if ($pageId <= 0) {
            throw new InvalidArgumentException('Failed to create gallery page.');
        }

        $menuId = $this->resolveMenuId();
        $menuItemId = $this->ensureMenuItemForPage($menuId, $pageId, (string) $gallery['name']);
        $pageUrl = (string) \get_permalink($pageId);

        return [
            'gallery_id' => $galleryId,
            'page_id' => $pageId,
            'page_url' => $pageUrl,
            'menu_id' => $menuId,
            'menu_item_id' => $menuItemId,
        ];
    }

    /**
     * @param array{id:int, name:string, description:string, template:string, created_at:string, image_ids:list<int>} $gallery
     */
    private function createGalleryPage(int $galleryId, array $gallery): int
    {
        $title = sprintf('%s Gallery', (string) $gallery['name']);
        $template = isset($gallery['template']) ? trim((string) $gallery['template']) : 'basic-grid';
        $template = \sanitize_key($template);

        if ($template === '') {
            $template = 'basic-grid';
        }

        $shortcode = sprintf(
            '[prox_gallery id="%d" template="%s"]',
            $galleryId,
            $template
        );

        $pageId = \wp_insert_post(
            [
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $title,
                'post_name' => \sanitize_title($title . '-' . $galleryId),
                'post_content' => $shortcode,
            ],
            true
        );

        if ($pageId instanceof \WP_Error || ! is_int($pageId) || $pageId <= 0) {
            return 0;
        }

        \update_post_meta($pageId, '_prox_gallery_id', $galleryId);

        return $pageId;
    }

    private function findExistingGalleryPageId(int $galleryId): int
    {
        $posts = \get_posts(
            [
                'post_type' => 'page',
                'post_status' => 'any',
                'meta_key' => '_prox_gallery_id',
                'meta_value' => $galleryId,
                'numberposts' => 1,
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => 'DESC',
            ]
        );

        if (! is_array($posts) || $posts === []) {
            return 0;
        }

        $id = (int) $posts[0];

        return $id > 0 ? $id : 0;
    }

    private function resolveMenuId(): int
    {
        $menus = \wp_get_nav_menus(
            [
                'orderby' => 'term_id',
                'order' => 'ASC',
            ]
        );

        if (is_array($menus) && $menus !== []) {
            $firstMenu = $menus[0];

            if ($firstMenu instanceof \WP_Term) {
                return (int) $firstMenu->term_id;
            }
        }

        $created = \wp_create_nav_menu('Prox Gallery Menu');

        if ($created instanceof \WP_Error || (int) $created <= 0) {
            throw new InvalidArgumentException('Failed to create navigation menu.');
        }

        $menuId = (int) $created;
        $registeredLocations = \get_registered_nav_menus();

        if ($registeredLocations !== []) {
            $locations = \get_theme_mod('nav_menu_locations');
            $normalizedLocations = is_array($locations) ? $locations : [];

            foreach ($registeredLocations as $location => $label) {
                if (! is_string($location) || $location === '') {
                    continue;
                }

                if (! isset($normalizedLocations[$location]) || (int) $normalizedLocations[$location] <= 0) {
                    $normalizedLocations[$location] = $menuId;
                    \set_theme_mod('nav_menu_locations', $normalizedLocations);
                    break;
                }
            }
        }

        return $menuId;
    }

    private function ensureMenuItemForPage(int $menuId, int $pageId, string $title): int
    {
        $items = \wp_get_nav_menu_items($menuId, ['post_status' => 'any']);

        if (is_array($items)) {
            foreach ($items as $item) {
                if (! $item instanceof \WP_Post) {
                    continue;
                }

                if ((int) $item->object_id === $pageId && (string) $item->object === 'page') {
                    return (int) $item->ID;
                }
            }
        }

        $menuItemId = \wp_update_nav_menu_item(
            $menuId,
            0,
            [
                'menu-item-title' => $title !== '' ? $title : sprintf('Gallery %d', $pageId),
                'menu-item-object' => 'page',
                'menu-item-object-id' => $pageId,
                'menu-item-type' => 'post_type',
                'menu-item-status' => 'publish',
            ]
        );

        if ($menuItemId instanceof \WP_Error || (int) $menuItemId <= 0) {
            throw new InvalidArgumentException('Failed to add page to navigation menu.');
        }

        return (int) $menuItemId;
    }
}
