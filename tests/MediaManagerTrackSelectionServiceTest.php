<?php

declare(strict_types=1);

use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerTrackSelectionService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\TrackUploadedImageService;

final class MediaManagerTrackSelectionServiceTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \delete_option('prox_gallery_uploaded_image_ids');
    }

    public function test_it_tracks_selected_image_attachment_ids(): void
    {
        $queue = new UploadedImageQueueModel();
        $service = new MediaManagerTrackSelectionService(new TrackUploadedImageService($queue));
        $trackedImageId = $this->createAttachment('image/jpeg', 'Tracked image');
        $skippedAttachmentId = $this->createAttachment('application/pdf', 'Skipped file');

        $result = $service->trackSelection(
            [
                'attachment_ids' => [
                    (string) $trackedImageId,
                    (string) $trackedImageId,
                    (string) $skippedAttachmentId,
                    '0',
                    'invalid',
                ],
            ]
        );

        self::assertSame(2, $result['requested_count']);
        self::assertSame(1, $result['tracked_count']);
        self::assertSame(1, $result['skipped_count']);
        self::assertSame([$trackedImageId], $result['tracked_ids']);
        self::assertSame([$skippedAttachmentId], $result['skipped_ids']);

        $items = $queue->all();
        self::assertCount(1, $items);
        self::assertSame($trackedImageId, $items[0]->id);
    }

    private function createAttachment(string $mimeType, string $title): int
    {
        $attachmentId = \wp_insert_attachment(
            [
                'post_title' => $title,
                'post_mime_type' => $mimeType,
                'post_type' => 'attachment',
                'post_status' => 'inherit',
            ],
            ''
        );

        self::assertIsInt($attachmentId);
        self::assertGreaterThan(0, $attachmentId);

        return $attachmentId;
    }
}
