<?php

declare(strict_types=1);

use Prox\ProxGallery\Modules\Admin\Services\TrackingSummaryService;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendTrackingService;
use Prox\ProxGallery\Modules\Gallery\Contracts\GalleryPageProvisionerInterface;
use Prox\ProxGallery\Modules\Gallery\Models\GalleryCollectionModel;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryService;
use Prox\ProxGallery\Modules\MediaLibrary\DTO\TrackedImageDto;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaCategoryService;

final class TrackingSummaryServiceTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \delete_option('prox_gallery_tracking_stats');
        \delete_option('prox_gallery_galleries');
        \delete_option('prox_gallery_uploaded_image_ids');
    }

    public function test_it_builds_phase_one_dashboard_summary_fields(): void
    {
        $queue = new UploadedImageQueueModel();
        $tracking = new FrontendTrackingService();
        $galleryService = new GalleryService(new GalleryCollectionModel(), $this->pageProvisioner());
        $categoryService = new MediaCategoryService();
        $categoryService->registerTaxonomy();

        $firstImageId = $this->createAttachment('Hero Portrait');
        $secondImageId = $this->createAttachment('Travel Detail');

        $firstDto = TrackedImageDto::fromAttachmentId($firstImageId);
        $secondDto = TrackedImageDto::fromAttachmentId($secondImageId);

        self::assertInstanceOf(TrackedImageDto::class, $firstDto);
        self::assertInstanceOf(TrackedImageDto::class, $secondDto);

        $queue->remember($firstDto);
        $queue->remember($secondDto);

        $categoryService->assignToAttachment($firstImageId, ['Portrait']);
        $categoryService->assignToAttachment($secondImageId, ['Portrait', 'Travel']);

        $gallery = $galleryService->create('Summer Story', '', 'masonry');
        $galleryService->setGalleryImages((int) $gallery['id'], [$firstImageId, $secondImageId]);
        $galleryService->create('Empty Studio', 'Studio planning references', 'basic-grid');

        $tracking->recordGalleryVisit((int) $gallery['id']);
        $tracking->recordGalleryVisit((int) $gallery['id']);
        $tracking->recordImageView((int) $gallery['id'], $firstImageId);
        $tracking->recordImageView((int) $gallery['id'], $firstImageId);
        $tracking->recordImageView((int) $gallery['id'], $secondImageId);

        $summaryService = new TrackingSummaryService($tracking, $galleryService, $queue);
        $summary = $summaryService->summary();

        self::assertSame(2, $summary['totals']['gallery_views']);
        self::assertSame(3, $summary['totals']['image_views']);
        self::assertSame(2, $summary['totals']['tracked_images']);
        self::assertSame(2, $summary['totals']['galleries']);
        self::assertSame(2, $summary['totals']['categories']);

        self::assertSame('Summer Story', $summary['spotlight']['gallery']['name']);
        self::assertSame('Hero Portrait', $summary['spotlight']['image']['title']);
        self::assertSame(2, $summary['galleries'][0]['image_count']);
        self::assertSame('masonry', $summary['galleries'][0]['template']);

        self::assertSame('Portrait', $summary['categories'][0]['name']);
        self::assertSame(2, $summary['categories'][0]['count']);
        self::assertSame('Travel', $summary['categories'][1]['name']);
        self::assertSame(1, $summary['categories'][1]['count']);

        self::assertGreaterThanOrEqual(2, count($summary['recent_activity']));
        self::assertSame(1, $summary['portfolio_gaps']['galleries_without_description']);
        self::assertSame(1, $summary['portfolio_gaps']['empty_galleries']);
        self::assertSame(0, $summary['portfolio_gaps']['uncategorized_images']);
    }

    private function createAttachment(string $title): int
    {
        $attachmentId = \wp_insert_attachment(
            [
                'post_title' => $title,
                'post_mime_type' => 'image/jpeg',
                'post_type' => 'attachment',
                'post_status' => 'inherit',
            ],
            ''
        );

        self::assertIsInt($attachmentId);
        self::assertGreaterThan(0, $attachmentId);

        return $attachmentId;
    }

    private function pageProvisioner(): GalleryPageProvisionerInterface
    {
        return new class implements GalleryPageProvisionerInterface
        {
            public function provisionForGallery(int $galleryId, array $gallery): array
            {
                return [
                    'page_id' => 0,
                    'page_url' => '',
                    'menu_id' => 0,
                    'menu_item_id' => 0,
                ];
            }
        };
    }
}
