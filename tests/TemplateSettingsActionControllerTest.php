<?php

declare(strict_types=1);

use Prox\ProxGallery\Controllers\TemplateSettingsActionController;
use Prox\ProxGallery\Services\TemplateCustomizationService;
use Prox\ProxGallery\States\AdminConfigurationState;

final class TemplateSettingsActionControllerTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \delete_option('prox_gallery_options');
    }

    public function test_it_registers_settings_actions_on_boot(): void
    {
        $controller = $this->controller();
        $controller->boot();

        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_template_settings_get', [$controller, 'handleAjaxRequest']));
        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_template_settings_update', [$controller, 'handleAjaxRequest']));
    }

    public function test_it_extends_admin_config_with_template_settings_actions(): void
    {
        $controller = $this->controller();
        $controller->boot();

        $payload = \apply_filters(
            'prox_gallery/admin/config_payload',
            [
                'screen' => '',
                'rest_nonce' => '',
                'ajax_url' => (string) \admin_url('admin-ajax.php'),
            ]
        );

        self::assertArrayHasKey('action_controllers', $payload);
        self::assertArrayHasKey('template_settings', $payload['action_controllers']);
        self::assertSame(
            'prox_gallery_template_settings_get',
            $payload['action_controllers']['template_settings']['get']['action']
        );
        self::assertSame(
            'prox_gallery_template_settings_update',
            $payload['action_controllers']['template_settings']['update']['action']
        );
    }

    public function test_it_gets_and_updates_template_settings(): void
    {
        $controller = $this->controller();
        $initial = $controller->getSettings([], 'prox_gallery_template_settings_get');
        $updated = $controller->updateSettings(
            [
                'basic_grid_columns' => 5,
                'basic_grid_lightbox' => 'false',
                'basic_grid_hover_zoom' => '1',
                'basic_grid_full_width' => '1',
                'basic_grid_transition' => 'slide',
                'masonry_columns' => 3,
                'masonry_lightbox' => '1',
                'masonry_hover_zoom' => '0',
                'masonry_full_width' => 'true',
                'masonry_transition' => 'implode',
            ],
            'prox_gallery_template_settings_update'
        );

        self::assertSame(4, $initial['settings']['basic_grid_columns']);
        self::assertSame('none', $initial['settings']['basic_grid_transition']);
        self::assertSame(5, $updated['settings']['basic_grid_columns']);
        self::assertFalse($updated['settings']['basic_grid_lightbox']);
        self::assertTrue($updated['settings']['basic_grid_hover_zoom']);
        self::assertTrue($updated['settings']['basic_grid_full_width']);
        self::assertSame('slide', $updated['settings']['basic_grid_transition']);
        self::assertSame(3, $updated['settings']['masonry_columns']);
        self::assertTrue($updated['settings']['masonry_lightbox']);
        self::assertFalse($updated['settings']['masonry_hover_zoom']);
        self::assertTrue($updated['settings']['masonry_full_width']);
        self::assertSame('implode', $updated['settings']['masonry_transition']);
    }

    private function controller(): TemplateSettingsActionController
    {
        return new TemplateSettingsActionController(
            new TemplateCustomizationService(new AdminConfigurationState())
        );
    }
}
