<?php

declare(strict_types=1);

use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerListService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\TrackUploadedImageService;

final class MediaManagerListServiceTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \delete_option('prox_gallery_uploaded_image_ids');
    }

    public function test_it_ignores_missing_attachments_without_mutating_storage_when_listing(): void
    {
        $queue = new UploadedImageQueueModel();
        $trackService = new TrackUploadedImageService($queue);
        $service = new MediaManagerListService($queue);
        $attachmentId = $this->createAttachment('image/jpeg', 'Still here');

        $trackService->track($attachmentId);
        $stored = \get_option($queue->optionKey());

        \wp_delete_attachment($attachmentId, true);

        $items = $service->listItems();

        self::assertSame([], $items);
        self::assertSame($stored, \get_option($queue->optionKey()));
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
