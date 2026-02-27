<?php

declare(strict_types=1);

/**
 * Isolated WordPress PHPUnit config for Prox Gallery.
 *
 * Use this file via:
 * WP_PHPUNIT__TESTS_CONFIG=./tests/wp-tests-config.php
 */

$wpRoot = getenv('WP_CORE_DIR');

if (! is_string($wpRoot) || $wpRoot === '') {
    $wpRoot = dirname(__DIR__, 3);
}

define('ABSPATH', rtrim($wpRoot, '/\\') . '/');

define('DB_NAME', (string) (getenv('WP_TEST_DB_NAME') ?: 'wordpress_test'));
define('DB_USER', (string) (getenv('WP_TEST_DB_USER') ?: getenv('WORDPRESS_DB_USER') ?: 'wordpress'));
define('DB_PASSWORD', (string) (getenv('WP_TEST_DB_PASSWORD') ?: getenv('WORDPRESS_DB_PASSWORD') ?: 'password'));
define('DB_HOST', (string) (getenv('WP_TEST_DB_HOST') ?: getenv('WORDPRESS_DB_HOST') ?: 'mysql'));
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

$table_prefix = (string) (getenv('WP_TEST_TABLE_PREFIX') ?: 'wptests_');

define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', false);

define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Prox Gallery Tests');

define('WP_PHP_BINARY', 'php');
