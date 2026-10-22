<?php
/**
 * Plugin Name: Prox Gallery Disposable Verification
 * Description: Admin-only verification fixture for an expendable TasteWP site. Blocks all OpenAI traffic while active.
 * Version: 1.0.0
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

use Prox\ProxGallery\Modules\Gallery\Models\GalleryCollectionModel;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\DTO\TrackedImageDto;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerListService;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendTrackingService;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiSettingsService;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiStoryService;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiUsageService;
use Prox\ProxGallery\Modules\OpenAi\Controllers\OpenAiActionController;
use Prox\ProxGallery\Controllers\ForbiddenActionException;
use Prox\ProxGallery\Storage\AtomicOptionStore;
use Prox\ProxGallery\States\AdminConfigurationState;

if (!preg_match('/^[a-z0-9-]+\.(?:s[0-9]+-)?tastewp\.com$/', (string) wp_parse_url(home_url(), PHP_URL_HOST))) {
    add_action('admin_notices', static fn () => print('<div class="notice notice-error"><p>Verification fixture only operates on disposable TasteWP sites.</p></div>'));
    return;
}

// No external provider request can leave the test site, including requests made by a failing test.
add_filter('pre_http_request', static function ($pre, $args, $url) {
    if (wp_parse_url($url, PHP_URL_HOST) !== 'api.openai.com') return $pre;
    (new AtomicOptionStore())->mutate('pg_verify_provider_calls', static function (array &$raw): void { $raw['count'] = ($raw['count'] ?? 0) + 1; });
    if (get_option('pg_verify_provider_failure')) return new WP_Error('simulated_timeout', 'Simulated provider timeout');
    return ['headers' => [], 'response' => ['code' => 200, 'message' => 'OK'], 'body' => wp_json_encode(['output_text' => wp_json_encode(['short_title' => 'Verified image', 'story' => 'A test image described by the intercepted provider response.'])])];
}, 10, 3);

function pg_verify_state(): array { return (array) get_option('pg_verify_state', []); }
function pg_verify_expect(bool $ok, string $name): array { return ['name' => $name, 'passed' => $ok]; }

add_action('admin_menu', static function (): void {
    add_management_page('Prox Gallery verification', 'Gallery verification', 'manage_options', 'pg-verification', 'pg_verify_page');
});

function pg_verify_page(): void
{
    if (!current_user_can('manage_options')) return;
    $config = ['url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('pg_verify'), 'actions' => []];
    foreach (['prox_gallery_openai_generate_story', 'prox_gallery_openai_config_get', 'prox_gallery_track_event', 'prox_gallery_galleries_list'] as $action) $config['actions'][$action] = wp_create_nonce($action);
    echo '<div class="wrap"><h1>Prox Gallery · WordPress verification</h1><p>Disposable test data only. OpenAI transport is intercepted; no paid requests leave this site.</p><p>Contact: marcel@prox-web.nl</p><button class="button button-primary" id="pg-run">Run WordPress verification</button><p id="pg-progress" role="status">Ready</p><pre id="pg-results" style="white-space:pre-wrap;background:#fff;padding:20px;max-width:1100px"></pre></div>';
    echo '<script type="application/json" id="pg-verify-config">'.wp_json_encode($config).'</script>';
    wp_enqueue_script('pg-verify-runner', plugins_url('runner.js', __FILE__), [], '1.0.0', true);
}

add_action('wp_ajax_pg_verify', static function (): void {
    check_ajax_referer('pg_verify');
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Administrator required'], 403);
    if (!class_exists(AtomicOptionStore::class)) wp_send_json_error(['message' => 'Activate Prox Gallery first'], 400);
    try { wp_send_json_success(pg_verify_operation(sanitize_key((string) ($_POST['operation'] ?? '')))); }
    catch (Throwable $e) { wp_send_json_error(['message' => $e->getMessage()], 500); }
});

function pg_verify_operation(string $operation): array
{
    $state = pg_verify_state();
    $queue = new UploadedImageQueueModel();
    $galleries = new GalleryCollectionModel();
    $settings = new OpenAiSettingsService(new AdminConfigurationState());
    $store = new AtomicOptionStore();
    if ($operation === 'seed') {
        if (!isset($state['file'])) {
            $upload = wp_upload_bits('pg-verification.png', null, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGMwLYkAAAHjAQI05oxgAAAAAElFTkSuQmCC'));
            if ($upload['error']) throw new RuntimeException($upload['error']);
            $state = ['file' => $upload['file'], 'url' => $upload['url'], 'ids' => [], 'admin' => get_current_user_id()];
            update_option('admin_email', 'marcel@prox-web.nl');
            wp_update_user(['ID' => get_current_user_id(), 'user_email' => 'marcel@prox-web.nl']);
        }
        for ($i = 0; $i < 50 && count($state['ids']) < 600; $i++) {
            $id = wp_insert_attachment(['post_title' => 'Verification image '.(count($state['ids']) + 1), 'post_mime_type' => 'image/png', 'post_author' => $state['admin'], 'guid' => $state['url']], $state['file']);
            if (is_wp_error($id) || !$id) throw new RuntimeException('Unable to create fixture attachment');
            wp_update_attachment_metadata($id, ['width' => 1, 'height' => 1, 'file' => _wp_relative_upload_path($state['file'])]);
            $queue->remember(TrackedImageDto::fromAttachmentId($id));
            $state['ids'][] = $id;
        }
        if (count($state['ids']) === 600 && empty($state['gallery'])) {
            $state['gallery'] = $galleries->create('Verification collection', 'Real WordPress test fixtures')['id'];
            $galleries->setGalleryImages($state['gallery'], array_slice($state['ids'], 0, 6));
            $state['page'] = wp_insert_post(['post_type' => 'page', 'post_title' => 'Gallery verification', 'post_content' => '[prox_gallery id="'.$state['gallery'].'"]', 'post_status' => 'publish']);
            $state['author'] = wp_insert_user(['user_login' => 'pg_verification_author', 'user_pass' => wp_generate_password(40), 'role' => 'author']);
        }
        update_option('pg_verify_state', $state, false);
        return ['seeded' => count($state['ids']), 'page' => isset($state['page']) ? get_permalink($state['page']) : null];
    }
    if (count($state['ids'] ?? []) !== 600) throw new RuntimeException('Prepare 600 fixtures first');

    if ($operation === 'checks') {
        $results = [];
        $ids = array_map(static fn ($image) => $image->id, $queue->all());
        $results[] = pg_verify_expect(count(array_intersect($state['ids'], $ids)) === 600, 'All 600 indexed attachments survive the old 500 boundary');
        $listed = (new MediaManagerListService($queue))->listItems();
        $results[] = pg_verify_expect(count(array_intersect($state['ids'], array_column($listed, 'id'))) === 600, 'Real media service returns all 600 WordPress attachments');
        $before = get_option($queue->optionKey());
        (new MediaManagerListService($queue))->listItems();
        $results[] = pg_verify_expect($before === get_option($queue->optionKey()), 'Listing media does not mutate persistent data');
        $queueBeforeCleanup = count($queue->all());
        $queue->remember(TrackedImageDto::fromArray(['id' => 999991, 'title' => 'Stale verification row']));
        $cleanup = $queue->removeIds([999991]);
        $results[] = pg_verify_expect($cleanup === ['removed' => 1, 'remaining' => $queueBeforeCleanup], 'Atomic cleanup removes only stale rows and returns committed counts');

        // Force a real SQL interleaving after the first read, before its compare-and-swap.
        $key = 'pg_verify_cas';
        update_option($key, ['value' => 'A', 'count' => 0], false);
        $interleave = null;
        $interleave = static function ($query) use (&$interleave, $key) {
            if (str_starts_with($query, 'UPDATE ') && str_contains($query, "option_name = '$key'")) {
                remove_filter('query', $interleave);
                update_option($key, ['value' => 'a', 'count' => 0], false);
            }
            return $query;
        };
        add_filter('query', $interleave);
        $store->mutate($key, static function (array &$raw): void { $raw['count']++; });
        remove_filter('query', $interleave);
        $results[] = pg_verify_expect($store->read($key) === ['value' => 'a', 'count' => 1], 'Actual SQL compare-and-swap replays after a competing write');
        $results[] = pg_verify_expect(get_option($key) === ['value' => 'a', 'count' => 1], 'Core option cache is invalidated after commit');

        $tracking = new FrontendTrackingService();
        $tracking->recordImageView($state['gallery'], $state['ids'][0]);
        $imageBefore = $tracking->stats()['galleries'][$state['gallery']]['images'][$state['ids'][0]]['total'];
        $tracking->recordGalleryVisit($state['gallery']);
        $results[] = pg_verify_expect($tracking->stats()['galleries'][$state['gallery']]['images'][$state['ids'][0]]['total'] === $imageBefore, 'Gallery visits preserve nested image statistics');

        $settings->update(['api_key' => 'fake-verification-key', 'user_daily_limit' => 20, 'site_daily_limit' => 100]);
        $controller = new OpenAiActionController($settings, new OpenAiStoryService($settings));
        $redacted = $controller->getSettings([], 'verify')['settings'];
        $results[] = pg_verify_expect($redacted['api_key'] === '' && $redacted['api_key_configured'], 'API key is encrypted at rest and redacted in response');
        $rawSettings = $store->read((new AdminConfigurationState())->optionKey());
        $results[] = pg_verify_expect(str_starts_with($rawSettings['openai']['api_key'], 'enc:v1:'), 'Stored API key has encrypted payload');

        wp_set_current_user($state['author']);
        try { $controller->applyStory(['attachment_id' => $state['ids'][0], 'story' => 'Forbidden change', 'short_title' => 'Forbidden'], 'verify'); $denied = false; }
        catch (ForbiddenActionException $e) { $denied = true; }
        $results[] = pg_verify_expect($denied, 'Real WordPress author cannot alter an administrator attachment');
        wp_set_current_user($state['admin']);

        $store->mutate('prox_gallery_openai_usage', static function (array &$raw): void { $raw = ['day' => '2000-01-01', 'site' => 999, 'users' => []]; });
        $usage = new OpenAiUsageService();
        $results[] = pg_verify_expect($usage->reserve($state['admin'], $settings->settings())['site_used'] === 1, 'UTC day rollover resets quota');
        update_option('pg_verify_provider_failure', true);
        try { (new OpenAiStoryService($settings))->generate(['attachment_id' => $state['ids'][0]]); $failed = false; }
        catch (RuntimeException $e) { $failed = $e->getMessage() === 'Simulated provider timeout'; }
        update_option('pg_verify_provider_failure', false);
        $results[] = pg_verify_expect($failed && $usage->status($state['admin'], $settings->settings())['site_used'] === 2, 'Provider timeout consumes one quota attempt');
        $settings->update(['user_daily_limit' => 0]);
        try { $usage->reserve($state['admin'], $settings->settings()); $disabled = false; }
        catch (\Prox\ProxGallery\Modules\OpenAi\Support\QuotaExceededException $e) { $disabled = true; }
        $results[] = pg_verify_expect($disabled, 'Zero disables generation for administrators too');
        $settings->update(['user_daily_limit' => 20]);
        return ['checks' => $results, 'environment' => ['wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION, 'database' => $GLOBALS['wpdb']->db_version(), 'email' => get_option('admin_email')], 'page' => get_permalink($state['page'])];
    }

    if ($operation === 'concurrency_start') {
        $store->mutate('prox_gallery_openai_usage', static function (array &$raw): void { $raw = []; });
        $store->mutate('pg_verify_provider_calls', static function (array &$raw): void { $raw = []; });
        $settings->update(['user_daily_limit' => 3, 'site_daily_limit' => 3]);
        $state['gallery_count_before'] = count($galleries->all());
        $stats = (new FrontendTrackingService())->stats();
        $state['visits_before'] = $stats['galleries'][$state['gallery']]['total'] ?? 0;
        $state['views_before'] = $stats['images'][$state['ids'][0]]['total'] ?? 0;
        update_option('pg_verify_state', $state, false);
        return ['gallery' => $state['gallery'], 'image' => $state['ids'][0]];
    }
    if ($operation === 'concurrent_gallery') return $galleries->create('Concurrent HTTP collection');
    if ($operation === 'concurrent_media') {
        $index = min(599, max(0, (int) ($_POST['index'] ?? 0)));
        $dto = TrackedImageDto::fromAttachmentId($state['ids'][$index]);
        $dto = TrackedImageDto::fromArray([...$dto->toArray(), 'title' => 'Concurrent HTTP image '.$index]);
        $queue->remember($dto);
        return ['updated' => $dto->id];
    }
    if ($operation === 'concurrency_finish') {
        $stats = (new FrontendTrackingService())->stats();
        $results = [];
        $results[] = pg_verify_expect(count($galleries->all()) === $state['gallery_count_before'] + 12, '12 concurrent HTTP gallery creates keep all rows');
        $galleryIds = array_column($galleries->all(), 'id');
        $results[] = pg_verify_expect(count($galleryIds) === count(array_unique($galleryIds)), 'Concurrent gallery IDs are unique');
        $updated = array_filter($queue->all(), static fn ($dto) => str_starts_with($dto->title, 'Concurrent HTTP image '));
        $results[] = pg_verify_expect(count($updated) === 12, '12 concurrent HTTP media updates are retained');
        $results[] = pg_verify_expect(($stats['galleries'][$state['gallery']]['total'] ?? 0) - $state['visits_before'] === 12, '12 concurrent public visits are counted');
        $results[] = pg_verify_expect(($stats['images'][$state['ids'][0]]['total'] ?? 0) - $state['views_before'] === 12, '12 concurrent public image views are counted');
        $usage = (new OpenAiUsageService())->status($state['admin'], $settings->settings());
        $calls = $store->read('pg_verify_provider_calls')['count'] ?? 0;
        $results[] = pg_verify_expect($usage['site_used'] === 3 && $usage['user_used'] === 3 && $calls === 3, 'Quota admits exactly 3 of 12 concurrent AI requests before provider dispatch');
        // Leave safe defaults and a blank provider key; no test key is retained for subsequent use.
        $settings->update(['api_key' => '', 'user_daily_limit' => 20, 'site_daily_limit' => 100]);
        return ['checks' => $results, 'usage' => $usage, 'intercepted_provider_calls' => $calls];
    }
    throw new InvalidArgumentException('Unknown verification operation');
}
