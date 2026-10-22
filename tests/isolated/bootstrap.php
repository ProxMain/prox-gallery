<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

// Explicit WordPress boundary doubles. These tests do not replace the WP integration suite.
function get_option(string $key, mixed $default = false): mixed { return $GLOBALS['test_options'][$key] ?? $default; }
function update_option(string $key, mixed $value, bool $autoload = false): bool { $GLOBALS['test_options'][$key] = $value; return true; }
function apply_filters(string $hook, mixed $value, mixed ...$args): mixed { return isset($GLOBALS['test_filters'][$hook]) ? $GLOBALS['test_filters'][$hook]($value, ...$args) : $value; }
function do_action(string $hook, mixed ...$args): void {}
function add_action(string $hook, mixed $callback): void {}
function current_user_can(string $capability, mixed ...$args): bool { $GLOBALS['test_cap_checks'][] = [$capability, ...$args]; return (bool) ($GLOBALS['test_can'][$capability . ':' . ($args[0] ?? '')] ?? false); }
function wp_unslash(mixed $value): mixed { return $value; }
function wp_verify_nonce(string $nonce, string $action): bool { return $nonce === 'valid'; }
function sanitize_key(string $value): string { return preg_replace('/[^a-z0-9_\-]/', '', strtolower($value)) ?? ''; }
function sanitize_text_field(string $value): string { return strip_tags($value); }
function sanitize_textarea_field(string $value): string { return strip_tags($value); }
function wp_json_encode(mixed $value): string { return (string) json_encode($value); }
function wp_salt(string $scheme = 'auth'): string { return 'unit-test-only-salt-not-a-production-secret'; }
function get_site_url(): string { return 'https://example.test'; }
function get_role(string $name): ?WP_Role { return $GLOBALS['test_roles'][$name] ?? null; }
function get_post(int $id): ?WP_Post { return $GLOBALS['test_posts'][$id] ?? null; }
function get_post_field(string $key, int $id): string { return (string) ($GLOBALS['test_posts'][$id]->{$key} ?? ''); }
function get_post_meta(int $id, string $key, bool $single = false): string { return ''; }
function wp_attachment_is_image(int $id): bool { return isset($GLOBALS['test_posts'][$id]); }
function taxonomy_exists(string $name): bool { return false; }
function wp_get_attachment_image_url(int $id, string $size): string { return 'https://example.test/' . $id . '.jpg'; }
function wp_update_post(array $post, bool $error = false): int { foreach ($post as $key => $value) { if ($key !== 'ID') $GLOBALS['test_posts'][$post['ID']]->{$key} = $value; } return $post['ID']; }
class WP_Post { public string $post_type = 'attachment'; public string $post_title = ''; public string $post_content = ''; public string $post_excerpt = ''; }
function maybe_serialize(mixed $value): string { return serialize($value); }
function maybe_unserialize(string $value): mixed { return unserialize($value, ['allowed_classes' => false]); }
function wp_cache_delete(string $key, string $group): bool { return true; }
function get_current_user_id(): int { return $GLOBALS['test_user_id'] ?? 1; }
function get_attached_file(int $id): string { return $GLOBALS['test_attachment_file'] ?? __FILE__; }
function get_post_mime_type(int $id): string { return 'image/png'; }
function wp_remote_post(string $url, array $args): mixed { $GLOBALS['test_provider_calls'] = ($GLOBALS['test_provider_calls'] ?? 0) + 1; return $GLOBALS['test_provider_response'] ?? new WP_Error(); }
function wp_remote_retrieve_response_code(array $response): int { return $response['response']['code']; }
function wp_remote_retrieve_body(array $response): string { return $response['body']; }
class WP_Error { public function get_error_message(): string { return 'Simulated provider timeout'; } }

// A deterministic SQL-boundary double. Real SQL and concurrent HTTP are checked on WordPress.
class TestOptionDatabase {
    public string $options = 'wp_options';
    public string $last_error = '';
    public mixed $beforeWrite = null;
    public bool $conflict = false;
    public bool $fail = false;
    public function prepare(string $sql, mixed ...$args): array { return [$sql, $args]; }
    public function get_var(array $query): ?string {
        $key = $query[1][0];
        return array_key_exists($key, $GLOBALS['test_options']) ? serialize($GLOBALS['test_options'][$key]) : null;
    }
    public function query(array $query): int|false {
        if ($this->fail) return false;
        $callback = $this->beforeWrite;
        $this->beforeWrite = null;
        if ($callback) $callback();
        if ($this->conflict) return 0;
        [$sql, $args] = $query;
        if (str_starts_with($sql, 'INSERT')) {
            [$key, $next] = $args;
            if (array_key_exists($key, $GLOBALS['test_options'])) return 0;
        } else {
            [$next, $key, $previous] = $args;
            if (!array_key_exists($key, $GLOBALS['test_options']) || serialize($GLOBALS['test_options'][$key]) !== $previous) return 0;
        }
        $GLOBALS['test_options'][$key] = unserialize($next, ['allowed_classes' => false]);
        return 1;
    }
}
$GLOBALS['wpdb'] = new TestOptionDatabase();
class WP_Role {
    public array $capabilities = [];
    public function has_cap(string $key): bool { return (bool) ($this->capabilities[$key] ?? false); }
    public function add_cap(string $key): void { $this->capabilities[$key] = true; }
}
