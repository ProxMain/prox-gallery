<?php

declare(strict_types=1);

use Prox\ProxGallery\Bootstrap\App;
use Prox\ProxGallery\Modules\MediaLibrary\DTO\TrackedImageDto;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;

final class MediaLibraryUploadTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \delete_option('prox_gallery_uploaded_image_ids');
    }

    public function test_it_tracks_new_image_uploads_for_gallery_overview(): void
    {
        App::make()->boot();

        $imageId = $this->createAttachment('image/jpeg');
        \do_action('prox_gallery/module/media_library/deferred_track', $imageId);
        $queue = new UploadedImageQueueModel();
        $tracked = $queue->all();

        self::assertCount(1, $tracked);
        self::assertInstanceOf(TrackedImageDto::class, $tracked[0]);
        self::assertSame($imageId, $tracked[0]->id);
        self::assertSame('image/jpeg', $tracked[0]->mimeType);
        self::assertIsString($tracked[0]->uploadedBy);
        self::assertIsString($tracked[0]->camera);
        self::assertIsString($tracked[0]->iso);
    }

    public function test_it_ignores_non_image_uploads(): void
    {
        App::make()->boot();

        $documentId = $this->createAttachment('application/pdf');
        \do_action('prox_gallery/module/media_library/deferred_track', $documentId);
        $queue = new UploadedImageQueueModel();

        foreach ($queue->all() as $tracked) {
            self::assertNotSame($documentId, $tracked->id);
        }
    }

    private function createAttachment(string $mimeType): int
    {
        $attachmentId = \wp_insert_attachment(
            [
                'post_title' => 'Prox Gallery Test Asset',
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
