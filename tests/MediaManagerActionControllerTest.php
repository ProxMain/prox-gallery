<?php

declare(strict_types=1);

use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaManagerActionController;
use Prox\ProxGallery\Controllers\Admin\AdminConfigContributorRegistry;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerListService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerMetadataService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerSyncService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerTrackSelectionService;
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
        $controller = $this->controller($queue);
        $controller->boot();

        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_media_manager_sync', [$controller, 'handleAjaxRequest']));
    }

    public function test_it_exposes_media_manager_action_config_to_admin_payload(): void
    {
        $queue = new UploadedImageQueueModel();
        $controller = $this->controller($queue);
        $registry = new AdminConfigContributorRegistry();
        $registry->addContributor($controller);
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
        self::assertArrayHasKey('update', $payload['action_controllers']['media_manager']);
        self::assertSame(
            'prox_gallery_media_manager_update',
            $payload['action_controllers']['media_manager']['update']['action']
        );
        self::assertIsString($payload['action_controllers']['media_manager']['update']['nonce']);
        self::assertNotSame('', $payload['action_controllers']['media_manager']['update']['nonce']);
        self::assertArrayHasKey('track_selected', $payload['action_controllers']['media_manager']);
        self::assertSame(
            'prox_gallery_media_manager_track_selected',
            $payload['action_controllers']['media_manager']['track_selected']['action']
        );
        self::assertIsString($payload['action_controllers']['media_manager']['track_selected']['nonce']);
        self::assertNotSame('', $payload['action_controllers']['media_manager']['track_selected']['nonce']);
    }

    public function test_it_updates_attachment_metadata(): void
    {
        $queue = new UploadedImageQueueModel();
        $controller = $this->controller($queue);
        $attachmentId = $this->createAttachment('image/jpeg', 'Old title');

        $response = $controller->updateTrackedImageMetadata(
            [
                'attachment_id' => $attachmentId,
                'title' => 'New title',
                'alt_text' => 'Alt text',
                'caption' => 'Caption text',
                'description' => 'Description text',
            ],
            'prox_gallery_media_manager_update'
        );

        self::assertSame($attachmentId, $response['attachment_id']);
        self::assertSame('New title', $response['item']['title']);
        self::assertSame('Alt text', $response['item']['alt_text']);
        self::assertSame('Caption text', $response['item']['caption']);
        self::assertSame('Description text', $response['item']['description']);
    }

    public function test_it_tracks_selected_attachments(): void
    {
        $queue = new UploadedImageQueueModel();
        $controller = $this->controller($queue);
        $imageAttachmentId = $this->createAttachment('image/jpeg', 'Track me');
        $skippedAttachmentId = $this->createAttachment('application/pdf', 'Skip me');

        $response = $controller->trackSelectedAttachments(
            [
                'attachment_ids' => [$imageAttachmentId, $skippedAttachmentId],
            ],
            'prox_gallery_media_manager_track_selected'
        );

        self::assertSame('prox_gallery_media_manager_track_selected', $response['action']);
        self::assertSame(2, $response['requested_count']);
        self::assertSame(1, $response['tracked_count']);
        self::assertSame(1, $response['skipped_count']);
        self::assertSame([$imageAttachmentId], $response['tracked_ids']);
        self::assertSame([$skippedAttachmentId], $response['skipped_ids']);
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

    private function controller(UploadedImageQueueModel $queue): MediaManagerActionController
    {
        $trackService = new TrackUploadedImageService($queue);

        return new MediaManagerActionController(
            $queue,
            $trackService,
            new MediaManagerListService($queue),
            new MediaManagerSyncService($queue, $trackService),
            new MediaManagerTrackSelectionService($trackService),
            new MediaManagerMetadataService($trackService)
        );
    }
}
