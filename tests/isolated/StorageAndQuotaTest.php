<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Prox\ProxGallery\Storage\AtomicOptionStore;
use Prox\ProxGallery\Storage\StorageBusyException;
use Prox\ProxGallery\Modules\Gallery\Models\GalleryCollectionModel;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendTrackingService;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\DTO\TrackedImageDto;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiUsageService;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiSettingsService;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiStoryService;
use Prox\ProxGallery\Modules\OpenAi\Support\QuotaExceededException;
use Prox\ProxGallery\States\AdminConfigurationState;

final class StorageAndQuotaTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wpdb'] = new TestOptionDatabase();
        $GLOBALS['test_options'] = [];
        $GLOBALS['test_filters'] = [];
        $GLOBALS['test_posts'] = [7 => new WP_Post()];
        $GLOBALS['test_user_id'] = 1;
        $GLOBALS['test_provider_calls'] = 0;
        $GLOBALS['test_provider_response'] = new WP_Error();
        $GLOBALS['test_attachment_file'] = tempnam(sys_get_temp_dir(), 'pg-fixture-');
        file_put_contents($GLOBALS['test_attachment_file'], 'test image bytes; provider transport is mocked');
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        unlink($GLOBALS['test_attachment_file']);
        unset($GLOBALS['test_attachment_file']);
    }

    public function test_two_gallery_creates_keep_both_rows_and_allocate_different_ids(): void
    {
        $collection = new GalleryCollectionModel();
        $GLOBALS['wpdb']->beforeWrite = static fn () => $collection->create('Competing request');
        $created = $collection->create('Original request');
        self::assertSame(2, $created['id']);
        self::assertSame(['Competing request', 'Original request'], array_column($collection->all(), 'name'));
    }

    public function test_rename_preserves_images_added_between_read_and_write(): void
    {
        $collection = new GalleryCollectionModel();
        $gallery = $collection->create('Before');
        $GLOBALS['wpdb']->beforeWrite = static fn () => $collection->addImagesToGallery($gallery['id'], [7]);
        $collection->rename($gallery['id'], 'After', 'Description');
        self::assertSame([7], $collection->find($gallery['id'])['image_ids']);
        self::assertSame('After', $collection->find($gallery['id'])['name']);
    }

    public function test_competing_tracking_updates_preserve_both_counts(): void
    {
        $tracking = new FrontendTrackingService();
        $GLOBALS['wpdb']->beforeWrite = static fn () => $tracking->recordImageView(3, 7);
        $tracking->recordGalleryVisit(3);
        self::assertSame(1, $tracking->stats()['galleries'][3]['total']);
        self::assertSame(1, $tracking->stats()['galleries'][3]['images'][7]['total']);
        self::assertSame(1, $tracking->stats()['images'][7]['total']);
    }

    public function test_more_than_500_images_and_competing_upload_are_retained(): void
    {
        $queue = new UploadedImageQueueModel();
        $images = [];
        for ($id = 1; $id <= 600; $id++) $images[] = TrackedImageDto::fromArray(['id' => $id, 'title' => 'Image '.$id]);
        $queue->replaceAll($images);
        $GLOBALS['wpdb']->beforeWrite = static fn () => $queue->remember(TrackedImageDto::fromArray(['id' => 601]));
        $queue->remember(TrackedImageDto::fromArray(['id' => 602]));
        self::assertCount(602, $queue->all());
        self::assertSame(range(1, 602), array_map(static fn ($image) => $image->id, $queue->all()));
    }

    public function test_cleanup_preserves_a_concurrent_upload(): void
    {
        $queue = new UploadedImageQueueModel();
        $queue->remember(TrackedImageDto::fromArray(['id' => 1]));
        $GLOBALS['wpdb']->beforeWrite = static fn () => $queue->remember(TrackedImageDto::fromArray(['id' => 2]));
        $result = $queue->removeIds([1]);
        self::assertSame(['removed' => 1, 'remaining' => 1], $result);
        self::assertSame([2], array_map(static fn ($image) => $image->id, $queue->all()));
    }

    public function test_site_and_user_quota_are_reserved_together_under_contention(): void
    {
        $usage = new OpenAiUsageService();
        $limits = ['user_daily_limit' => 1, 'site_daily_limit' => 1];
        $GLOBALS['wpdb']->beforeWrite = static fn () => $usage->reserve(2, $limits);
        try { $usage->reserve(1, $limits); self::fail('Quota must reject the losing request'); }
        catch (QuotaExceededException $e) { self::assertStringContainsString('quota', $e->getMessage()); }
        self::assertSame(1, $usage->status(1, $limits)['site_used']);
        self::assertSame(0, $usage->status(1, $limits)['user_used']);
        self::assertSame(1, $usage->status(2, $limits)['user_used']);
    }

    public function test_user_limit_does_not_spend_another_site_reservation(): void
    {
        $usage = new OpenAiUsageService();
        $limits = ['user_daily_limit' => 1, 'site_daily_limit' => 3];
        $usage->reserve(1, $limits);
        try { $usage->reserve(1, $limits); self::fail('User quota must be enforced'); }
        catch (QuotaExceededException $e) {}
        self::assertSame(2, $usage->reserve(2, $limits)['site_used']);
    }

    public function test_previous_day_is_reset_and_zero_disables_generation(): void
    {
        $GLOBALS['test_options']['prox_gallery_openai_usage'] = ['day' => '2000-01-01', 'site' => 999, 'users' => ['1' => 999]];
        $usage = new OpenAiUsageService();
        self::assertSame(1, $usage->reserve(1, ['user_daily_limit' => 2, 'site_daily_limit' => 2])['site_used']);
        $this->expectException(QuotaExceededException::class);
        $usage->reserve(1, ['user_daily_limit' => 0, 'site_daily_limit' => 2]);
    }

    public function test_provider_timeout_counts_and_next_request_never_reaches_provider(): void
    {
        $settings = new OpenAiSettingsService(new AdminConfigurationState());
        $settings->update(['api_key' => 'fake-test-key', 'user_daily_limit' => 1, 'site_daily_limit' => 1]);
        $stories = new OpenAiStoryService($settings);
        try { $stories->generate(['attachment_id' => 7]); self::fail('Provider timeout must be reported'); }
        catch (RuntimeException $e) { self::assertSame('Simulated provider timeout', $e->getMessage()); }
        try { $stories->generate(['attachment_id' => 7]); self::fail('Quota must block request'); }
        catch (QuotaExceededException $e) {}
        self::assertSame(1, $GLOBALS['test_provider_calls']);
    }

    public function test_invalid_input_does_not_consume_quota(): void
    {
        $settings = new OpenAiSettingsService(new AdminConfigurationState());
        $settings->update(['api_key' => 'fake-test-key']);
        try { (new OpenAiStoryService($settings))->generate(['attachment_id' => 7, 'template_key' => 'missing']); self::fail('Unknown template must fail'); }
        catch (InvalidArgumentException $e) {}
        self::assertSame(0, (new OpenAiUsageService())->status(1, $settings->settings())['site_used']);
        self::assertSame(0, $GLOBALS['test_provider_calls']);
    }

    public function test_repeated_contention_fails_without_overwriting_data(): void
    {
        $GLOBALS['test_options']['busy'] = ['count' => 1];
        $GLOBALS['wpdb']->conflict = true;
        $this->expectException(StorageBusyException::class);
        (new AtomicOptionStore())->mutate('busy', static function (array &$raw): void { $raw['count']++; });
    }

    public function test_database_write_failure_is_reported(): void
    {
        $GLOBALS['wpdb']->fail = true;
        $this->expectException(RuntimeException::class);
        (new AtomicOptionStore())->mutate('failure', static function (array &$raw): void { $raw = ['value' => 1]; });
    }

    public function test_competing_settings_sections_preserve_each_others_changes(): void
    {
        $state = new AdminConfigurationState();
        $templates = new \Prox\ProxGallery\Modules\Admin\Services\TemplateCustomizationService($state);
        $settings = new OpenAiSettingsService($state);
        $GLOBALS['wpdb']->beforeWrite = static fn () => $templates->update(['basic_grid_columns' => 3]);
        $settings->update(['api_key' => 'fake-test-key', 'site_daily_limit' => 12]);
        self::assertSame(3, $templates->settings()['basic_grid_columns']);
        self::assertSame(12, $settings->settings()['site_daily_limit']);
        self::assertSame('fake-test-key', $settings->settings()['api_key']);
    }

    public function test_ajax_translates_quota_exhaustion_to_http_429(): void
    {
        $controller = new class extends \Prox\ProxGallery\Controllers\AbstractActionController {
            public int $status = 0;
            public function id(): string { return 'quota-test'; }
            protected function actions(): array { return ['quota' => ['callback' => 'run', 'nonce_action' => 'quota', 'capability' => 'manage_options']]; }
            public function run(array $payload, string $action): array { throw new QuotaExceededException('Daily quota reached'); }
            protected function sendError(array $payload, int $status = 400): void { $this->status = $status; }
        };
        $controller->boot();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_REQUEST = ['action' => 'quota', '_ajax_nonce' => 'valid'];
        $GLOBALS['test_can']['manage_options:'] = true;
        $controller->handleAjaxRequest();
        self::assertSame(429, $controller->status);
    }
}
