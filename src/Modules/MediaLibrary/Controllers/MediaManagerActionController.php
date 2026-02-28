<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Controllers;

use Prox\ProxGallery\Controllers\AbstractActionController;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\TrackUploadedImageService;

/**
 * Handles secured Media Manager AJAX actions.
 */
final class MediaManagerActionController extends AbstractActionController
{
    private const ACTION_LIST = 'prox_gallery_media_manager_list';
    private const ACTION_SYNC = 'prox_gallery_media_manager_sync';

    public function __construct(
        private UploadedImageQueueModel $queue,
        private TrackUploadedImageService $trackService
    )
    {
    }

    public function id(): string
    {
        return 'media_manager.actions';
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
                'callback' => 'listTrackedImages',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
            self::ACTION_SYNC => [
                'callback' => 'syncOverview',
                'nonce_action' => self::ACTION_SYNC,
                'capability' => 'manage_options',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function listTrackedImages(array $payload, string $action): array
    {
        $items = [];

        foreach ($this->queue->all() as $image) {
            $items[] = [
                'id' => $image->id,
                'title' => $image->title,
                'mime_type' => $image->mimeType,
                'uploaded_at' => $image->uploadedAt,
                'uploaded_by' => $image->uploadedBy,
                'url' => $image->url,
                'width' => $image->width,
                'height' => $image->height,
                'file_size' => $image->fileSize,
            ];
        }

        /**
         * Filters tracked media list payload before responding.
         *
         * @param list<array<string, mixed>> $items
         * @param array<string, mixed>        $payload
         */
        $items = (array) \apply_filters('prox_gallery/module/media_manager/list_payload', $items, $payload);

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
    public function syncOverview(array $payload, string $action): array
    {
        $attachmentIds = \get_posts(
            [
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'post_mime_type' => 'image',
                'numberposts' => -1,
                'fields' => 'ids',
            ]
        );
        $syncedCount = 0;

        if (is_array($attachmentIds)) {
            foreach ($attachmentIds as $attachmentId) {
                if ($this->trackService->track((int) $attachmentId)) {
                    $syncedCount++;
                }
            }
        }

        $tracked = $this->queue->all();

        /**
         * Fires after media manager sync data is prepared.
         *
         * @param array<string, mixed> $payload
         * @param list<\Prox\ProxGallery\Modules\MediaLibrary\DTO\TrackedImageDto> $tracked
         */
        \do_action('prox_gallery/module/media_manager/synced', $payload, $tracked);

        return [
            'action' => $action,
            'synced_count' => $syncedCount,
            'tracked_count' => count($tracked),
            'synced_at' => \gmdate('c'),
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

        $controllers['media_manager'] = [
            'list' => [
                'action' => self::ACTION_LIST,
                'nonce' => \wp_create_nonce(self::ACTION_LIST),
            ],
            'sync' => [
                'action' => self::ACTION_SYNC,
                'nonce' => \wp_create_nonce(self::ACTION_SYNC),
            ],
        ];

        $config['action_controllers'] = $controllers;

        return $config;
    }
}
