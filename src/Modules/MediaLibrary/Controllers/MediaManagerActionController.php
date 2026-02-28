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
    private const ACTION_UPDATE = 'prox_gallery_media_manager_update';

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
            self::ACTION_UPDATE => [
                'callback' => 'updateTrackedImageMetadata',
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
    public function listTrackedImages(array $payload, string $action): array
    {
        $items = [];

        foreach ($this->queue->all() as $image) {
            $post = \get_post($image->id);
            $viewUrl = (string) $image->url;
            $editUrl = \function_exists('get_edit_post_link')
                ? (string) (\get_edit_post_link($image->id, '') ?? '')
                : '';
            $deleteUrl = \function_exists('get_delete_post_link')
                ? (string) (\get_delete_post_link($image->id, '', true) ?? '')
                : '';
            $altText = (string) \get_post_meta($image->id, '_wp_attachment_image_alt', true);
            $caption = $post instanceof \WP_Post ? (string) $post->post_excerpt : '';
            $description = $post instanceof \WP_Post ? (string) $post->post_content : '';
            $categories = [];

            if (\taxonomy_exists('prox_media_category')) {
                $terms = \wp_get_object_terms(
                    $image->id,
                    'prox_media_category',
                    [
                        'orderby' => 'name',
                        'order' => 'ASC',
                    ]
                );

                if (is_array($terms)) {
                    foreach ($terms as $term) {
                        if (! $term instanceof \WP_Term) {
                            continue;
                        }

                        $categories[] = [
                            'id' => (int) $term->term_id,
                            'name' => (string) $term->name,
                            'slug' => (string) $term->slug,
                        ];
                    }
                }
            }

            $items[] = [
                'id' => $image->id,
                'title' => $image->title,
                'mime_type' => $image->mimeType,
                'uploaded_at' => $image->uploadedAt,
                'uploaded_by' => $image->uploadedBy,
                'url' => $image->url,
                'view_url' => $viewUrl,
                'edit_url' => $editUrl,
                'delete_url' => $deleteUrl,
                'alt_text' => $altText,
                'caption' => $caption,
                'description' => $description,
                'categories' => $categories,
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
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function updateTrackedImageMetadata(array $payload, string $action): array
    {
        $attachmentId = isset($payload['attachment_id']) ? (int) $payload['attachment_id'] : 0;

        if ($attachmentId <= 0) {
            throw new \InvalidArgumentException('Attachment ID is required.');
        }

        $post = \get_post($attachmentId);

        if (! $post instanceof \WP_Post || $post->post_type !== 'attachment') {
            throw new \InvalidArgumentException('Attachment not found.');
        }

        $title = isset($payload['title']) ? \sanitize_text_field((string) $payload['title']) : (string) $post->post_title;
        $altText = isset($payload['alt_text']) ? \sanitize_text_field((string) $payload['alt_text']) : (string) \get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
        $caption = isset($payload['caption']) ? \sanitize_text_field((string) $payload['caption']) : (string) $post->post_excerpt;
        $description = isset($payload['description']) ? \sanitize_textarea_field((string) $payload['description']) : (string) $post->post_content;

        $updatedPost = [
            'ID' => $attachmentId,
            'post_title' => $title,
            'post_excerpt' => $caption,
            'post_content' => $description,
        ];
        $result = \wp_update_post($updatedPost, true);

        if ($result instanceof \WP_Error) {
            throw new \RuntimeException($result->get_error_message());
        }

        \update_post_meta($attachmentId, '_wp_attachment_image_alt', $altText);
        $this->trackService->track($attachmentId);

        $updated = \get_post($attachmentId);

        return [
            'action' => $action,
            'attachment_id' => $attachmentId,
            'item' => [
                'id' => $attachmentId,
                'title' => $updated instanceof \WP_Post ? (string) $updated->post_title : $title,
                'alt_text' => (string) \get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
                'caption' => $updated instanceof \WP_Post ? (string) $updated->post_excerpt : $caption,
                'description' => $updated instanceof \WP_Post ? (string) $updated->post_content : $description,
            ],
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
            'update' => [
                'action' => self::ACTION_UPDATE,
                'nonce' => \wp_create_nonce(self::ACTION_UPDATE),
            ],
        ];

        $config['action_controllers'] = $controllers;

        return $config;
    }
}
