<?php
/**
 * Plugin Name:       Prox Gallery
 * Description:       Modular, service-oriented WordPress gallery plugin built with a PSR-4 architecture.
 * Version:           0.1.0
 * Requires at least: 6.9.1
 * Requires PHP:      8.1
 * Author:            Marcel Santing
 * Author URI:        mailto:marcel@prox-web.nl
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       prox-gallery
 *
 * @package           ProxGallery
 * @since             0.1.0
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

const PROX_GALLERY_FILE = __FILE__;
const PROX_GALLERY_DIR  = __DIR__;

$autoloader = PROX_GALLERY_DIR . '/vendor/autoload_packages.php';

if (! is_readable($autoloader)) {
    return;
}

require_once $autoloader;

Prox\ProxGallery\Bootstrap\Plugin::boot();