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
class WP_Error {}
class WP_Role {
    public array $capabilities = [];
    public function has_cap(string $key): bool { return (bool) ($this->capabilities[$key] ?? false); }
    public function add_cap(string $key): void { $this->capabilities[$key] = true; }
}
