<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers\Admin;

use Prox\ProxGallery\Policies\AdminCapabilityPolicy;

/**
 * Registers and renders the plugin's admin menu page.
 */
final class AdminMenuRegistrar
{
    private const MENU_SLUG = 'prox-gallery';

    public function register(callable $renderPage, bool $canManage): string
    {
        $hookSuffix = \add_menu_page(
            'Prox Gallery',
            'Prox Gallery',
            $canManage ? AdminCapabilityPolicy::CAPABILITY_MANAGE : 'do_not_allow',
            self::MENU_SLUG,
            $renderPage,
            'dashicons-format-gallery',
            58
        );

        return is_string($hookSuffix) ? $hookSuffix : '';
    }

    public function render(bool $canManage): void
    {
        if (! $canManage) {
            \wp_die(
                \esc_html__('You do not have permission to access this page.', 'prox-gallery')
            );
        }

        echo '<div class="wrap prox-gallery-admin-wrap">';
        echo '<div id="prox-gallery-admin-root"></div>';
        echo '</div>';
    }
}
