<?php

declare(strict_types=1);

if (! defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
	define(
		'WP_TESTS_PHPUNIT_POLYFILLS_PATH',
		dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills'
	);
}

$_testsDir = getenv('WP_TESTS_DIR');

if (! is_string($_testsDir) || $_testsDir === '') {
	$_testsDir = '/tmp/wordpress-tests-lib';
}

require_once $_testsDir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', static function (): void {
	require dirname(__DIR__) . '/prox-gallery.php';
});

require_once $_testsDir . '/includes/bootstrap.php';