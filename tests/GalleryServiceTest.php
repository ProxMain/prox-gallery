<?php

declare(strict_types=1);

use Prox\ProxGallery\Modules\Gallery\Models\GalleryCollectionModel;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryPageProvisioningService;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryService;

final class GalleryServiceTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \delete_option('prox_gallery_galleries');
    }

    protected function tearDown(): void
    {
        \delete_option('prox_gallery_galleries');
        parent::tearDown();
    }

    public function test_it_clears_all_galleries_via_the_repository_boundary(): void
    {
        $service = $this->service();

        $service->create('First');
        $service->create('Second');

        self::assertCount(2, $service->list());

        $service->clearAll();

        self::assertSame([], $service->list());
        self::assertSame([], \get_option('prox_gallery_galleries', []));
    }

    private function service(): GalleryService
    {
        return new GalleryService(
            new GalleryCollectionModel(),
            new GalleryPageProvisioningService()
        );
    }
}
