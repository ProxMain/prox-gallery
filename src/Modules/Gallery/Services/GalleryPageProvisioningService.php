<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Gallery\Services;

use InvalidArgumentException;
use Prox\ProxGallery\Modules\Gallery\Contracts\GalleryPageProvisionerInterface;

/**
 * Provisions a frontend page and menu item for a gallery.
 */
final class GalleryPageProvisioningService implements GalleryPageProvisionerInterface
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
    public function provisionForGallery(int $galleryId, array $gallery): array
    {
        $existingPageId = $this->findExistingGalleryPageId($galleryId);
        $pageId = $existingPageId > 0 ? $existingPageId : $this->createGalleryPage($galleryId, $gallery);

        if ($pageId <= 0) {
            throw new InvalidArgumentException('Failed to create gallery page.');
        }

        $menuId = $this->resolveMenuId();
        $menuItemId = $this->ensureMenuItemForPage($menuId, $pageId, (string) $gallery['name']);
        $pageUrl = (string) \get_permalink($pageId);

        return [
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
