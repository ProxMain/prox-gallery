<?php

declare(strict_types=1);

use Prox\ProxGallery\Modules\Gallery\Controllers\GalleryActionController;
use Prox\ProxGallery\Modules\Gallery\Models\GalleryCollectionModel;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryService;
use Prox\ProxGallery\Models\GalleryModel;
use Prox\ProxGallery\Policies\FrontendVisibilityPolicy;
use Prox\ProxGallery\Services\FrontendGalleryService;
use Prox\ProxGallery\Services\TemplateCustomizationService;
use Prox\ProxGallery\States\FrontendGalleryState;
use Prox\ProxGallery\States\AdminConfigurationState;

final class GalleryActionControllerTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \delete_option('prox_gallery_galleries');
        \delete_option('prox_gallery_options');
    }

    public function test_it_registers_gallery_ajax_actions_on_boot(): void
    {
        $controller = $this->controller();
        $controller->boot();

        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_gallery_list', [$controller, 'handleAjaxRequest']));
        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_gallery_create', [$controller, 'handleAjaxRequest']));
        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_gallery_rename', [$controller, 'handleAjaxRequest']));
        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_gallery_delete', [$controller, 'handleAjaxRequest']));
        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_gallery_list_image_galleries', [$controller, 'handleAjaxRequest']));
        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_gallery_set_image_galleries', [$controller, 'handleAjaxRequest']));
        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_gallery_add_images', [$controller, 'handleAjaxRequest']));
        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_gallery_set_images', [$controller, 'handleAjaxRequest']));
        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_gallery_create_page', [$controller, 'handleAjaxRequest']));
    }

    public function test_it_exposes_gallery_action_config_to_admin_payload(): void
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

        self::assertIsArray($payload);
        self::assertArrayHasKey('action_controllers', $payload);
        self::assertArrayHasKey('galleries', $payload['action_controllers']);
        self::assertSame(
            'prox_gallery_gallery_list',
            $payload['action_controllers']['galleries']['list']['action']
        );
        self::assertSame(
            'prox_gallery_gallery_create',
            $payload['action_controllers']['galleries']['create']['action']
        );
        self::assertSame(
            'prox_gallery_gallery_rename',
            $payload['action_controllers']['galleries']['rename']['action']
        );
        self::assertSame(
            'prox_gallery_gallery_delete',
            $payload['action_controllers']['galleries']['delete']['action']
        );
        self::assertSame(
            'prox_gallery_gallery_list_image_galleries',
            $payload['action_controllers']['galleries']['list_image_galleries']['action']
        );
        self::assertSame(
            'prox_gallery_gallery_set_image_galleries',
            $payload['action_controllers']['galleries']['set_image_galleries']['action']
        );
        self::assertSame(
            'prox_gallery_gallery_add_images',
            $payload['action_controllers']['galleries']['add_images']['action']
        );
        self::assertSame(
            'prox_gallery_gallery_set_images',
            $payload['action_controllers']['galleries']['set_images']['action']
        );
        self::assertSame(
            'prox_gallery_gallery_create_page',
            $payload['action_controllers']['galleries']['create_page']['action']
        );
        self::assertArrayHasKey('templates', $payload['action_controllers']['galleries']);
        self::assertNotEmpty($payload['action_controllers']['galleries']['templates']);
    }

    public function test_it_creates_renames_deletes_and_lists_galleries(): void
    {
        $controller = $this->controller();

        $create = $controller->createGallery(
            [
                'name' => 'Summer Collection',
                'description' => 'Beach shots',
                'template' => 'basic-grid',
            ],
            'prox_gallery_gallery_create'
        );
        $renamed = $controller->renameGallery(
            [
                'id' => (int) $create['item']['id'],
                'name' => 'Summer 2026',
                'description' => 'Updated',
                'template' => 'basic-grid',
            ],
            'prox_gallery_gallery_rename'
        );
        $list = $controller->listGalleries([], 'prox_gallery_gallery_list');

        self::assertSame('Summer Collection', $create['item']['name']);
        self::assertSame('Summer 2026', $renamed['item']['name']);
        self::assertSame(1, $list['count']);
        self::assertSame('Summer 2026', $list['items'][0]['name']);

        $deleted = $controller->deleteGallery(
            [
                'id' => (int) $create['item']['id'],
            ],
            'prox_gallery_gallery_delete'
        );
        $listAfterDelete = $controller->listGalleries([], 'prox_gallery_gallery_list');

        self::assertSame((int) $create['item']['id'], $deleted['deleted_id']);
        self::assertSame(0, $listAfterDelete['count']);
    }

    public function test_it_stores_per_gallery_display_overrides(): void
    {
        $controller = $this->controller();
        $created = $controller->createGallery(
            [
                'name' => 'Display',
                'description' => '',
                'grid_columns_override' => 5,
                'lightbox_override' => '0',
                'hover_zoom_override' => '1',
                'full_width_override' => '1',
                'transition_override' => 'fade',
            ],
            'prox_gallery_gallery_create'
        );

        self::assertSame(5, $created['item']['grid_columns_override']);
        self::assertFalse($created['item']['lightbox_override']);
        self::assertTrue($created['item']['hover_zoom_override']);
        self::assertTrue($created['item']['full_width_override']);
        self::assertSame('fade', $created['item']['transition_override']);
    }

    public function test_it_sets_image_galleries_and_adds_images_to_gallery(): void
    {
        $controller = $this->controller();
        $first = $controller->createGallery(['name' => 'First'], 'prox_gallery_gallery_create');
        $second = $controller->createGallery(['name' => 'Second'], 'prox_gallery_gallery_create');

        $set = $controller->setImageGalleries(
            [
                'image_id' => 15,
                'gallery_ids' => [(int) $first['item']['id'], (int) $second['item']['id']],
            ],
            'prox_gallery_gallery_set_image_galleries'
        );
        $listed = $controller->listImageGalleries(
            [
                'image_id' => 15,
            ],
            'prox_gallery_gallery_list_image_galleries'
        );
        $added = $controller->addImagesToGallery(
            [
                'gallery_id' => (int) $first['item']['id'],
                'image_ids' => [20, 21],
            ],
            'prox_gallery_gallery_add_images'
        );
        $setImages = $controller->setGalleryImages(
            [
                'gallery_id' => (int) $first['item']['id'],
                'image_ids' => [21, 20],
            ],
            'prox_gallery_gallery_set_images'
        );

        self::assertCount(2, $set['gallery_ids']);
        self::assertCount(2, $listed['gallery_ids']);
        self::assertSame((int) $first['item']['id'], $added['item']['id']);
        self::assertSame(3, $added['item']['image_count']);
        self::assertSame([21, 20], $setImages['item']['image_ids']);
    }

    public function test_it_creates_frontend_page_and_menu_item_for_gallery(): void
    {
        $controller = $this->controller();
        $created = $controller->createGallery(
            [
                'name' => 'Launch',
                'description' => 'Landing gallery',
            ],
            'prox_gallery_gallery_create'
        );

        $page = $controller->createGalleryPage(
            [
                'id' => (int) $created['item']['id'],
            ],
            'prox_gallery_gallery_create_page'
        );

        self::assertGreaterThan(0, (int) $page['page_id']);
        self::assertGreaterThan(0, (int) $page['menu_id']);
        self::assertGreaterThan(0, (int) $page['menu_item_id']);
        self::assertIsString($page['page_url']);
        self::assertNotSame('', $page['page_url']);
    }

    private function controller(): GalleryActionController
    {
        $model = new GalleryCollectionModel();
        $service = new GalleryService($model);
        $frontendService = new FrontendGalleryService(
            new FrontendGalleryState(),
            new FrontendVisibilityPolicy(),
            new GalleryModel(),
            new TemplateCustomizationService(new AdminConfigurationState())
        );

        return new GalleryActionController($service, $frontendService);
    }
}
