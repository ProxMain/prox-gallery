<?php

declare(strict_types=1);

use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaManagerActionController;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\TrackUploadedImageService;

final class MediaManagerActionControllerTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \delete_option('prox_gallery_uploaded_image_ids');
    }

    public function test_it_registers_nonce_protected_ajax_actions_on_boot(): void
    {
        $queue = new UploadedImageQueueModel();
        $controller = new MediaManagerActionController($queue, new TrackUploadedImageService($queue));
        $controller->boot();

        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_media_manager_sync', [$controller, 'handleAjaxRequest']));
    }

    public function test_it_exposes_media_manager_action_config_to_admin_payload(): void
    {
        $queue = new UploadedImageQueueModel();
        $controller = new MediaManagerActionController($queue, new TrackUploadedImageService($queue));
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
        self::assertIsArray($payload['action_controllers']);
        self::assertArrayHasKey('media_manager', $payload['action_controllers']);
        self::assertIsArray($payload['action_controllers']['media_manager']);
        self::assertArrayHasKey('list', $payload['action_controllers']['media_manager']);
        self::assertSame(
            'prox_gallery_media_manager_list',
            $payload['action_controllers']['media_manager']['list']['action']
        );
        self::assertIsString($payload['action_controllers']['media_manager']['list']['nonce']);
        self::assertNotSame('', $payload['action_controllers']['media_manager']['list']['nonce']);
        self::assertArrayHasKey('sync', $payload['action_controllers']['media_manager']);
        self::assertSame(
            'prox_gallery_media_manager_sync',
            $payload['action_controllers']['media_manager']['sync']['action']
        );
        self::assertIsString($payload['action_controllers']['media_manager']['sync']['nonce']);
        self::assertNotSame('', $payload['action_controllers']['media_manager']['sync']['nonce']);
    }
}
