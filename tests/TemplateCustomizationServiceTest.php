<?php

declare(strict_types=1);

use Prox\ProxGallery\Services\TemplateCustomizationService;
use Prox\ProxGallery\States\AdminConfigurationState;

final class TemplateCustomizationServiceTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \delete_option('prox_gallery_options');
    }

    public function test_it_returns_default_template_settings(): void
    {
        $service = new TemplateCustomizationService(new AdminConfigurationState());
        $settings = $service->settings();

        self::assertSame(4, $settings['basic_grid_columns']);
        self::assertTrue($settings['basic_grid_lightbox']);
        self::assertTrue($settings['basic_grid_hover_zoom']);
        self::assertFalse($settings['basic_grid_full_width']);
        self::assertSame('none', $settings['basic_grid_transition']);
        self::assertSame(4, $settings['masonry_columns']);
        self::assertTrue($settings['masonry_lightbox']);
        self::assertTrue($settings['masonry_hover_zoom']);
        self::assertFalse($settings['masonry_full_width']);
        self::assertSame('none', $settings['masonry_transition']);
    }

    public function test_it_updates_and_normalizes_template_settings(): void
    {
        $service = new TemplateCustomizationService(new AdminConfigurationState());
        $updated = $service->update(
            [
                'basic_grid_columns' => 9,
                'basic_grid_lightbox' => '0',
                'basic_grid_hover_zoom' => 'true',
                'basic_grid_full_width' => '1',
                'basic_grid_transition' => 'explode',
                'masonry_columns' => 1,
                'masonry_lightbox' => 'yes',
                'masonry_hover_zoom' => '0',
                'masonry_full_width' => 'true',
                'masonry_transition' => 'fade',
            ]
        );

        self::assertSame(6, $updated['basic_grid_columns']);
        self::assertFalse($updated['basic_grid_lightbox']);
        self::assertTrue($updated['basic_grid_hover_zoom']);
        self::assertTrue($updated['basic_grid_full_width']);
        self::assertSame('explode', $updated['basic_grid_transition']);
        self::assertSame(2, $updated['masonry_columns']);
        self::assertTrue($updated['masonry_lightbox']);
        self::assertFalse($updated['masonry_hover_zoom']);
        self::assertTrue($updated['masonry_full_width']);
        self::assertSame('fade', $updated['masonry_transition']);
    }
}
