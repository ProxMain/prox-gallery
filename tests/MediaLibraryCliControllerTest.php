<?php

declare(strict_types=1);

use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaLibraryCliController;
use Prox\ProxGallery\Modules\MediaLibrary\DTO\TrackedImageDto;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\TrackUploadedImageService;

final class MediaLibraryCliControllerTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \delete_option('prox_gallery_uploaded_image_ids');
    }

    public function test_it_lists_only_ids_tracked_by_the_plugin(): void
    {
        $trackedImageId = $this->createAttachment('image/jpeg', 'Tracked image');
        $untrackedImageId = $this->createAttachment('image/jpeg', 'Untracked image');

        $queue = new UploadedImageQueueModel();
        $tracked = TrackedImageDto::fromAttachmentId($trackedImageId);
        self::assertInstanceOf(TrackedImageDto::class, $tracked);
        \update_option($queue->optionKey(), [$tracked->toArray()], false);

        $controller = new MediaLibraryCliController($queue, new TrackUploadedImageService($queue));
        $rows = $controller->rows();

        self::assertCount(1, $rows);
        self::assertSame($trackedImageId, $rows[0]['id']);
        self::assertNotSame($untrackedImageId, $rows[0]['id']);
        self::assertArrayHasKey('width', $rows[0]);
        self::assertArrayHasKey('height', $rows[0]);
        self::assertArrayHasKey('file_size', $rows[0]);
        self::assertArrayHasKey('camera', $rows[0]);
        self::assertArrayHasKey('iso', $rows[0]);
        self::assertArrayHasKey('uploaded_by', $rows[0]);
        self::assertIsString($rows[0]['uploaded_by']);
    }

    public function test_it_returns_empty_rows_when_no_images_were_tracked(): void
    {
        $queue = new UploadedImageQueueModel();
        $controller = new MediaLibraryCliController($queue, new TrackUploadedImageService($queue));

        self::assertSame([], $controller->rows());
    }

    public function test_it_tracks_an_image_by_id_on_manual_cli_track(): void
    {
        $imageId = $this->createAttachment('image/jpeg', 'Tracked via CLI');
        $queue = new UploadedImageQueueModel();
        $controller = new MediaLibraryCliController($queue, new TrackUploadedImageService($queue));

        self::assertTrue($controller->trackAttachment($imageId));
        self::assertCount(1, $controller->rows());
        self::assertSame($imageId, $controller->rows()[0]['id']);
    }

    public function test_it_does_not_track_non_images_on_manual_cli_track(): void
    {
        $documentId = $this->createAttachment('application/pdf', 'PDF via CLI');
        $queue = new UploadedImageQueueModel();
        $controller = new MediaLibraryCliController($queue, new TrackUploadedImageService($queue));

        self::assertFalse($controller->trackAttachment($documentId));
        self::assertSame([], $controller->rows());
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
