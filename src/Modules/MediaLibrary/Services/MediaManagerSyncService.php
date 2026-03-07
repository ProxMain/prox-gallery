<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Services;

use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;

/**
 * Synchronizes tracked media against existing image attachments.
 */
final class MediaManagerSyncService
{
    public function __construct(
        private UploadedImageQueueModel $queue,
        private TrackUploadedImageService $trackService
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{
     *     synced_count:int,
     *     tracked_count:int,
     *     synced_at:string
     * }
     */
    public function sync(array $payload = []): array
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
            'synced_count' => $syncedCount,
            'tracked_count' => count($tracked),
            'synced_at' => \gmdate('c'),
        ];
    }
}
