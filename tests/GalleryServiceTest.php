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

    public function test_it_normalizes_gallery_settings_in_the_service_boundary(): void
    {
        $service = $this->service();

        $created = $service->create(
            '  Summer Gallery  ',
            '  Beach shots  ',
            '  ',
            '9',
            '0',
            '1',
            'inherit',
            'FADE',
            '0',
            '1'
        );

        self::assertSame('Summer Gallery', $created['name']);
        self::assertSame('Beach shots', $created['description']);
        self::assertSame('basic-grid', $created['template']);
        self::assertSame(6, $created['grid_columns_override']);
        self::assertFalse($created['lightbox_override']);
        self::assertTrue($created['hover_zoom_override']);
        self::assertNull($created['full_width_override']);
        self::assertSame('fade', $created['transition_override']);
        self::assertFalse($created['show_title']);
        self::assertTrue($created['show_description']);
    }

    public function test_it_reuses_the_same_normalization_rules_when_renaming(): void
    {
        $service = $this->service();
        $created = $service->create('Original');

        $updated = $service->rename(
            (int) $created['id'],
            '  Updated Name  ',
            '  Updated Description  ',
            '  ',
            '1',
            'yes',
            'no',
            '1',
            true,
            'unknown',
            'false',
            'true'
        );

        self::assertSame('Updated Name', $updated['name']);
        self::assertSame('Updated Description', $updated['description']);
        self::assertSame('basic-grid', $updated['template']);
        self::assertSame(2, $updated['grid_columns_override']);
        self::assertTrue($updated['lightbox_override']);
        self::assertFalse($updated['hover_zoom_override']);
        self::assertTrue($updated['full_width_override']);
        self::assertNull($updated['transition_override']);
        self::assertFalse($updated['show_title']);
        self::assertTrue($updated['show_description']);
    }

    private function service(): GalleryService
    {
        return new GalleryService(
            new GalleryCollectionModel(),
            new GalleryPageProvisioningService()
        );
    }
}
