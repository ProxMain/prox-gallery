<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Gallery\Controllers;

use Prox\ProxGallery\Contracts\AdminConfigContributorInterface;
use Prox\ProxGallery\Controllers\AbstractActionController;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryService;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendGalleryService;
use Prox\ProxGallery\Policies\AdminCapabilityPolicy;

/**
 * Handles admin AJAX actions for gallery management.
 */
final class GalleryActionController extends AbstractActionController implements AdminConfigContributorInterface
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
    ) {}

    public function id(): string
    {
        return 'gallery.actions';
    }

    /**
     * @return array<string, array{callback:string, nonce_action?:string, capability?:string}>
     */
    protected function actions(): array
    {
        return [
            self::ACTION_LIST => [
                'callback' => 'listGalleries',
                'nonce_action' => self::ACTION_LIST,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
            ],
            self::ACTION_CREATE => [
                'callback' => 'createGallery',
                'nonce_action' => self::ACTION_CREATE,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
            ],
            self::ACTION_RENAME => [
                'callback' => 'renameGallery',
                'nonce_action' => self::ACTION_RENAME,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
            ],
            self::ACTION_DELETE => [
                'callback' => 'deleteGallery',
                'nonce_action' => self::ACTION_DELETE,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
            ],
            self::ACTION_LIST_IMAGE_GALLERIES => [
                'callback' => 'listImageGalleries',
                'nonce_action' => self::ACTION_LIST_IMAGE_GALLERIES,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
            ],
            self::ACTION_SET_IMAGE_GALLERIES => [
                'callback' => 'setImageGalleries',
                'nonce_action' => self::ACTION_SET_IMAGE_GALLERIES,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
            ],
            self::ACTION_ADD_IMAGES => [
                'callback' => 'addImagesToGallery',
                'nonce_action' => self::ACTION_ADD_IMAGES,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
            ],
            self::ACTION_SET_IMAGES => [
                'callback' => 'setGalleryImages',
                'nonce_action' => self::ACTION_SET_IMAGES,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
            ],
            self::ACTION_CREATE_PAGE => [
                'callback' => 'createGalleryPage',
                'nonce_action' => self::ACTION_CREATE_PAGE,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
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
        $item = $this->service->create(
            $name,
            $description,
            $template,
            $payload['grid_columns_override'] ?? null,
            $payload['lightbox_override'] ?? null,
            $payload['hover_zoom_override'] ?? null,
            $payload['full_width_override'] ?? null,
            $payload['transition_override'] ?? null,
            $payload['show_title'] ?? true,
            $payload['show_description'] ?? true
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
            || array_key_exists('transition_override', $payload)
            || array_key_exists('show_title', $payload)
            || array_key_exists('show_description', $payload);
        $item = $this->service->rename(
            $id,
            $name,
            $description,
            $template,
            $payload['grid_columns_override'] ?? null,
            $payload['lightbox_override'] ?? null,
            $payload['hover_zoom_override'] ?? null,
            $payload['full_width_override'] ?? null,
            $hasDisplayOverrides,
            $payload['transition_override'] ?? null,
            $payload['show_title'] ?? null,
            $payload['show_description'] ?? null
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
        return $this->extendAdminActionConfig(
            $config,
            'galleries',
            [
                'list' => self::ACTION_LIST,
                'create' => self::ACTION_CREATE,
                'rename' => self::ACTION_RENAME,
                'delete' => self::ACTION_DELETE,
                'list_image_galleries' => self::ACTION_LIST_IMAGE_GALLERIES,
                'set_image_galleries' => self::ACTION_SET_IMAGE_GALLERIES,
                'add_images' => self::ACTION_ADD_IMAGES,
                'set_images' => self::ACTION_SET_IMAGES,
                'create_page' => self::ACTION_CREATE_PAGE,
            ],
            [
                'templates' => $this->frontendGalleryService->templateCatalog(),
            ]
        );
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

}
