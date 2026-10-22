<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Prox\ProxGallery\Controllers\AbstractActionController;
use Prox\ProxGallery\Controllers\ForbiddenActionException;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendTrackingService;
use Prox\ProxGallery\Modules\OpenAi\Controllers\OpenAiActionController;
use Prox\ProxGallery\Modules\OpenAi\OpenAiModule;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiSettingsService;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiStoryService;
use Prox\ProxGallery\States\AdminConfigurationState;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerListService;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;

final class SecurityRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wpdb'] = new TestOptionDatabase();
        $GLOBALS['test_options'] = $GLOBALS['test_filters'] = $GLOBALS['test_can'] = $GLOBALS['test_cap_checks'] = [];
        $GLOBALS['test_posts'] = [7 => new WP_Post()];
        $GLOBALS['test_roles'] = array_fill_keys(['administrator', 'editor', 'author'], new WP_Role());
        $_SERVER = ['REQUEST_METHOD' => 'POST'];
        $_POST = $_REQUEST = [];
    }

    private function controller(): OpenAiActionController
    {
        $settings = new OpenAiSettingsService(new AdminConfigurationState());
        return new OpenAiActionController($settings, new OpenAiStoryService($settings));
    }

    public function test_ai_apply_denies_a_foreign_attachment_before_it_is_changed(): void
    {
        try {
            $this->controller()->applyStory(['attachment_id' => 7, 'short_title' => 'Changed', 'story' => 'Changed'], 'apply');
            self::fail('Foreign attachment should be forbidden.');
        } catch (ForbiddenActionException) {
            self::assertSame('', $GLOBALS['test_posts'][7]->post_title);
            self::assertContains(['edit_post', 7], $GLOBALS['test_cap_checks']);
        }
    }

    public function test_ai_generation_denies_a_foreign_attachment_before_settings_or_file_reads(): void
    {
        $this->expectException(ForbiddenActionException::class);
        $this->controller()->generateStory(['attachment_id' => 7], 'generate');
    }

    public function test_authorised_image_metadata_can_be_applied(): void
    {
        $GLOBALS['test_can']['edit_post:7'] = true;
        $response = $this->controller()->applyStory(['attachment_id' => 7, 'short_title' => 'Quiet forest', 'story' => 'Trees in morning light.'], 'apply');
        self::assertSame('Quiet forest', $response['short_title']);
        self::assertSame('Trees in morning light.', $GLOBALS['test_posts'][7]->post_content);
    }

    public function test_api_key_never_leaves_the_admin_settings_response_and_blank_preserves_it(): void
    {
        $GLOBALS['test_options']['prox_gallery_options'] = ['openai' => ['api_key' => 'test-only-key']];
        $response = $this->controller()->getSettings([], 'get');
        self::assertSame('', $response['settings']['api_key']);
        self::assertTrue($response['settings']['api_key_configured']);
        $response = $this->controller()->updateSettings(['api_key' => '', 'model' => 'configured-model'], 'update');
        self::assertSame('', $response['settings']['api_key']);
        self::assertTrue($response['settings']['api_key_configured']);
        self::assertStringStartsWith('enc:v1:', $GLOBALS['test_options']['prox_gallery_options']['openai']['api_key']);
        self::assertSame('test-only-key', (new OpenAiSettingsService(new AdminConfigurationState()))->settings()['api_key']);
    }

    public function test_gallery_visits_preserve_per_image_view_counts(): void
    {
        $service = new FrontendTrackingService();
        $service->recordImageView(3, 7);
        $service->recordGalleryVisit(3);
        $service->recordImageView(3, 7);
        $service->recordGalleryVisit(3);
        $stats = $service->stats();
        self::assertSame(2, $stats['galleries']['3']['total']);
        self::assertSame(2, $stats['galleries']['3']['images']['7']['total']);
        self::assertSame(2, $stats['images']['7']['total']);
    }

    public function test_country_headers_are_not_trusted_without_explicit_server_integration(): void
    {
        $_SERVER['HTTP_CF_IPCOUNTRY'] = 'US';
        $_SERVER['HTTP_X_COUNTRY'] = 'US';
        $service = new FrontendTrackingService();
        $service->recordGalleryVisit(3);
        self::assertSame(['ZZ' => 1], $service->stats()['galleries']['3']['countries']);
        $GLOBALS['test_filters']['prox_gallery/tracking/country_code'] = static fn (): string => 'NL';
        $service->recordGalleryVisit(3);
        self::assertSame(['ZZ' => 1, 'NL' => 1], $service->stats()['galleries']['3']['countries']);
    }

    public function test_media_listing_uses_the_current_attachment_title_after_an_ai_edit(): void
    {
        $GLOBALS['test_options']['prox_gallery_uploaded_image_ids'] = [['id' => 7, 'title' => 'Cached title']];
        $GLOBALS['test_posts'][7]->post_title = 'Current title';
        $items = (new MediaManagerListService(new UploadedImageQueueModel()))->listItems();
        self::assertSame('Current title', $items[0]['title']);
    }

    public function test_media_listing_does_not_rewrite_storage_while_filtering_deleted_attachments(): void
    {
        $stored = [['id' => 999, 'title' => 'Removed attachment']];
        $GLOBALS['test_options']['prox_gallery_uploaded_image_ids'] = $stored;
        self::assertSame([], (new MediaManagerListService(new UploadedImageQueueModel()))->listItems());
        self::assertSame($stored, $GLOBALS['test_options']['prox_gallery_uploaded_image_ids']);
    }

    public function test_revoked_ai_capability_is_not_silently_regranted_on_next_request(): void
    {
        $module = new OpenAiModule();
        $module->boot();
        self::assertTrue($GLOBALS['test_roles']['author']->has_cap(OpenAiModule::CAPABILITY_USE));
        $GLOBALS['test_roles']['author']->capabilities[OpenAiModule::CAPABILITY_USE] = false;
        $module->boot();
        self::assertFalse($GLOBALS['test_roles']['author']->has_cap(OpenAiModule::CAPABILITY_USE));
    }

    public function test_ajax_rejects_get_requests_and_missing_object_permission(): void
    {
        $controller = new class extends AbstractActionController {
            public array $response = [];
            public function id(): string { return 'test'; }
            protected function actions(): array { return ['secured' => ['callback' => 'run', 'nonce_action' => 'secured', 'capability' => 'manage_options']]; }
            public function run(array $payload, string $action): array { throw new ForbiddenActionException(); }
            protected function sendError(array $payload, int $status = 400): void { $this->response = ['status' => $status, 'payload' => $payload]; }
        };
        $controller->boot();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $controller->handleAjaxRequest();
        self::assertSame(405, $controller->response['status']);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_REQUEST = ['action' => 'secured', '_ajax_nonce' => 'valid'];
        $GLOBALS['test_can']['manage_options:'] = true;
        $controller->handleAjaxRequest();
        self::assertSame(403, $controller->response['status']);
    }
}
