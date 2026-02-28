<?php

declare(strict_types=1);

use Prox\ProxGallery\Modules\MediaLibrary\Controllers\MediaCategoryActionController;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaCategoryService;

final class MediaCategoryActionControllerTest extends WP_UnitTestCase
{
    private MediaCategoryService $service;
    private MediaCategoryActionController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MediaCategoryService();
        $this->service->registerTaxonomy();
        $this->controller = new MediaCategoryActionController($this->service, new UploadedImageQueueModel());
        $this->controller->boot();
    }

    public function test_it_registers_nonce_protected_category_actions_on_boot(): void
    {
        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_media_category_suggest', [$this->controller, 'handleAjaxRequest']));
        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_media_category_list', [$this->controller, 'handleAjaxRequest']));
        self::assertNotFalse(\has_action('wp_ajax_prox_gallery_media_category_assign', [$this->controller, 'handleAjaxRequest']));
    }

    public function test_it_exposes_media_category_action_config_to_admin_payload(): void
    {
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
        self::assertArrayHasKey('media_category', $payload['action_controllers']);
        self::assertSame(
            'prox_gallery_media_category_suggest',
            $payload['action_controllers']['media_category']['suggest']['action']
        );
        self::assertSame(
            'prox_gallery_media_category_list',
            $payload['action_controllers']['media_category']['list']['action']
        );
        self::assertSame(
            'prox_gallery_media_category_assign',
            $payload['action_controllers']['media_category']['assign']['action']
        );
        self::assertSame(
            MediaCategoryService::TAXONOMY,
            $payload['action_controllers']['media_category']['taxonomy']
        );
    }

    public function test_it_assigns_and_suggests_categories_through_controller_methods(): void
    {
        $attachmentId = $this->createAttachment('image/jpeg', 'Category Test');

        $assignResponse = $this->controller->assignCategoriesToAttachment(
            [
                'attachment_id' => $attachmentId,
                'categories' => ['Travel', 'Mountain'],
            ],
            'prox_gallery_media_category_assign'
        );

        self::assertSame(2, $assignResponse['count']);

        $suggestResponse = $this->controller->suggestCategories(
            [
                'query' => 'trav',
            ],
            'prox_gallery_media_category_suggest'
        );

        self::assertGreaterThanOrEqual(1, $suggestResponse['count']);
        self::assertSame('Travel', $suggestResponse['items'][0]['name']);
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
