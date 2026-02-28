<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Gallery\Controllers;

use Prox\ProxGallery\Controllers\AbstractActionController;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryService;
use Prox\ProxGallery\Services\FrontendGalleryService;

/**
 * Handles admin AJAX actions for gallery management.
 */
final class GalleryActionController extends AbstractActionController
{
    private const ACTION_LIST = 'prox_gallery_gallery_list';
    private const ACTION_CREATE = 'prox_gallery_gallery_create';
    private const ACTION_RENAME = 'prox_gallery_gallery_rename';
    private const ACTION_DELETE = 'prox_gallery_gallery_delete';
    private const ACTION_LIST_IMAGE_GALLERIES = 'prox_gallery_gallery_list_image_galleries';
    private const ACTION_SET_IMAGE_GALLERIES = 'prox_gallery_gallery_set_image_galleries';
    private const ACTION_ADD_IMAGES = 'prox_gallery_gallery_add_images';
    private const ACTION_SET_IMAGES = 'prox_gallery_gallery_set_images';
    private const ACTION_CREATE_PAGE = 'prox_gallery_gallery_create_page';

    public function __construct(
        private GalleryService $service,
        private FrontendGalleryService $frontendGalleryService
    )
    {
    }

    public function id(): string
    {
        return 'gallery.actions';
    }

    public function boot(): void
    {
        parent::boot();

        \add_filter('prox_gallery/admin/config_payload', [$this, 'extendAdminConfig']);
    }

    /**
     * @return array<string, array{callback:string, nonce_action?:string, capability?:string}>
     */
    protected function actions(): array
    {
        return [
            self::ACTION_LIST => [
                'callback' => 'listGalleries',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
            self::ACTION_CREATE => [
                'callback' => 'createGallery',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
            self::ACTION_RENAME => [
                'callback' => 'renameGallery',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
            self::ACTION_DELETE => [
                'callback' => 'deleteGallery',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
            self::ACTION_LIST_IMAGE_GALLERIES => [
                'callback' => 'listImageGalleries',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
            self::ACTION_SET_IMAGE_GALLERIES => [
                'callback' => 'setImageGalleries',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
            self::ACTION_ADD_IMAGES => [
                'callback' => 'addImagesToGallery',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
            self::ACTION_SET_IMAGES => [
                'callback' => 'setGalleryImages',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
            self::ACTION_CREATE_PAGE => [
                'callback' => 'createGalleryPage',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function listGalleries(array $payload, string $action): array
    {
        $items = $this->service->list();

        return [
            'action' => $action,
            'items' => $items,
            'count' => count($items),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function createGallery(array $payload, string $action): array
    {
        $name = isset($payload['name']) ? (string) $payload['name'] : '';
        $description = isset($payload['description']) ? (string) $payload['description'] : '';
        $template = isset($payload['template']) ? (string) $payload['template'] : 'basic-grid';
        $gridColumnsOverride = $this->nullableIntOverride($payload['grid_columns_override'] ?? null);
        $lightboxOverride = $this->nullableBoolOverride($payload['lightbox_override'] ?? null);
        $hoverZoomOverride = $this->nullableBoolOverride($payload['hover_zoom_override'] ?? null);
        $fullWidthOverride = $this->nullableBoolOverride($payload['full_width_override'] ?? null);
        $transitionOverride = $this->nullableTransitionOverride($payload['transition_override'] ?? null);
        $item = $this->service->create(
            $name,
            $description,
            $template,
            $gridColumnsOverride,
            $lightboxOverride,
            $hoverZoomOverride,
            $fullWidthOverride,
            $transitionOverride
        );

        return [
            'action' => $action,
            'item' => $item,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function renameGallery(array $payload, string $action): array
    {
        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        $name = isset($payload['name']) ? (string) $payload['name'] : '';
        $description = isset($payload['description']) ? (string) $payload['description'] : '';
        $template = isset($payload['template']) ? (string) $payload['template'] : null;
        $hasDisplayOverrides = array_key_exists('grid_columns_override', $payload)
            || array_key_exists('lightbox_override', $payload)
            || array_key_exists('hover_zoom_override', $payload)
            || array_key_exists('full_width_override', $payload)
            || array_key_exists('transition_override', $payload);
        $gridColumnsOverride = $this->nullableIntOverride($payload['grid_columns_override'] ?? null);
        $lightboxOverride = $this->nullableBoolOverride($payload['lightbox_override'] ?? null);
        $hoverZoomOverride = $this->nullableBoolOverride($payload['hover_zoom_override'] ?? null);
        $fullWidthOverride = $this->nullableBoolOverride($payload['full_width_override'] ?? null);
        $transitionOverride = $this->nullableTransitionOverride($payload['transition_override'] ?? null);
        $item = $this->service->rename(
            $id,
            $name,
            $description,
            $template,
            $gridColumnsOverride,
            $lightboxOverride,
            $hoverZoomOverride,
            $fullWidthOverride,
            $hasDisplayOverrides,
            $transitionOverride
        );

        return [
            'action' => $action,
            'item' => $item,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function deleteGallery(array $payload, string $action): array
    {
        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        $this->service->delete($id);

        return [
            'action' => $action,
            'deleted_id' => $id,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function listImageGalleries(array $payload, string $action): array
    {
        $imageId = isset($payload['image_id']) ? (int) $payload['image_id'] : 0;
        $galleryIds = $this->service->listGalleryIdsForImage($imageId);

        return [
            'action' => $action,
            'image_id' => $imageId,
            'gallery_ids' => $galleryIds,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function setImageGalleries(array $payload, string $action): array
    {
        $imageId = isset($payload['image_id']) ? (int) $payload['image_id'] : 0;
        $galleryIds = $this->intIdsFromPayload($payload['gallery_ids'] ?? []);
        $updated = $this->service->setImageGalleries($imageId, $galleryIds);

        return [
            'action' => $action,
            'image_id' => $imageId,
            'gallery_ids' => $updated,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function addImagesToGallery(array $payload, string $action): array
    {
        $galleryId = isset($payload['gallery_id']) ? (int) $payload['gallery_id'] : 0;
        $imageIds = $this->intIdsFromPayload($payload['image_ids'] ?? []);
        $item = $this->service->addImagesToGallery($galleryId, $imageIds);

        return [
            'action' => $action,
            'item' => $item,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function setGalleryImages(array $payload, string $action): array
    {
        $galleryId = isset($payload['gallery_id']) ? (int) $payload['gallery_id'] : 0;
        $imageIds = $this->intIdsFromPayload($payload['image_ids'] ?? []);
        $item = $this->service->setGalleryImages($galleryId, $imageIds);

        return [
            'action' => $action,
            'item' => $item,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function createGalleryPage(array $payload, string $action): array
    {
        $galleryId = isset($payload['id']) ? (int) $payload['id'] : 0;
        $result = $this->service->createFrontendPageAndMenuItem($galleryId);

        return [
            'action' => $action,
            ...$result,
        ];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public function extendAdminConfig(array $config): array
    {
        $controllers = [];

        if (isset($config['action_controllers']) && is_array($config['action_controllers'])) {
            $controllers = $config['action_controllers'];
        }

        $controllers['galleries'] = [
            'list' => [
                'action' => self::ACTION_LIST,
                'nonce' => \wp_create_nonce(self::ACTION_LIST),
            ],
            'create' => [
                'action' => self::ACTION_CREATE,
                'nonce' => \wp_create_nonce(self::ACTION_CREATE),
            ],
            'rename' => [
                'action' => self::ACTION_RENAME,
                'nonce' => \wp_create_nonce(self::ACTION_RENAME),
            ],
            'delete' => [
                'action' => self::ACTION_DELETE,
                'nonce' => \wp_create_nonce(self::ACTION_DELETE),
            ],
            'list_image_galleries' => [
                'action' => self::ACTION_LIST_IMAGE_GALLERIES,
                'nonce' => \wp_create_nonce(self::ACTION_LIST_IMAGE_GALLERIES),
            ],
            'set_image_galleries' => [
                'action' => self::ACTION_SET_IMAGE_GALLERIES,
                'nonce' => \wp_create_nonce(self::ACTION_SET_IMAGE_GALLERIES),
            ],
            'add_images' => [
                'action' => self::ACTION_ADD_IMAGES,
                'nonce' => \wp_create_nonce(self::ACTION_ADD_IMAGES),
            ],
            'set_images' => [
                'action' => self::ACTION_SET_IMAGES,
                'nonce' => \wp_create_nonce(self::ACTION_SET_IMAGES),
            ],
            'create_page' => [
                'action' => self::ACTION_CREATE_PAGE,
                'nonce' => \wp_create_nonce(self::ACTION_CREATE_PAGE),
            ],
            'templates' => $this->frontendGalleryService->templateCatalog(),
        ];

        $config['action_controllers'] = $controllers;

        return $config;
    }

    /**
     * @param mixed $value
     *
     * @return list<int>
     */
    private function intIdsFromPayload(mixed $value): array
    {
        if (is_string($value)) {
            $parts = array_map('trim', explode(',', $value));
            return array_values(
                array_filter(
                    array_map(static fn (string $item): int => (int) $item, $parts),
                    static fn (int $id): bool => $id > 0
                )
            );
        }

        if (! is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $id) {
            $normalized = (int) $id;

            if ($normalized <= 0) {
                continue;
            }

            $ids[] = $normalized;
        }

        return array_values(array_unique($ids));
    }

    private function nullableBoolOverride(mixed $value): ?bool
    {
        if ($value === null || $value === '' || $value === 'inherit') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return (bool) $value;
    }

    private function nullableIntOverride(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'inherit') {
            return null;
        }

        $int = (int) $value;

        if ($int < 2) {
            return 2;
        }

        if ($int > 6) {
            return 6;
        }

        return $int;
    }

    private function nullableTransitionOverride(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'inherit') {
            return null;
        }

        $normalized = strtolower(trim((string) $value));
        $allowed = ['none', 'slide', 'fade', 'explode', 'implode'];

        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        return null;
    }
}
