<?php

declare(strict_types=1);

use Prox\ProxGallery\Controllers\AdminGalleryController;

final class AdminGalleryControllerTest extends WP_UnitTestCase
{
    public function test_it_registers_admin_hooks_on_admin_requests(): void
    {
        if (! \function_exists('set_current_screen')) {
            $this->markTestSkipped('set_current_screen is not available.');
        }

        \set_current_screen('dashboard');

        $controller = new AdminGalleryController();
        $controller->boot();

        self::assertNotFalse(\has_action('admin_menu', [$controller, 'registerMenu']));
        self::assertNotFalse(\has_action('admin_enqueue_scripts', [$controller, 'enqueueAdminAssets']));
    }

    public function test_it_does_not_register_admin_hooks_on_frontend_requests(): void
    {
        if (! \function_exists('set_current_screen')) {
            $this->markTestSkipped('set_current_screen is not available.');
        }

        \set_current_screen('front');

        $controller = new AdminGalleryController();
        $controller->boot();

        self::assertFalse(\has_action('admin_menu', [$controller, 'registerMenu']));
        self::assertFalse(\has_action('admin_enqueue_scripts', [$controller, 'enqueueAdminAssets']));
    }

    public function test_it_renders_react_mount_point_for_admin_page(): void
    {
        $adminId = self::factory()->user->create(['role' => 'administrator']);
        \wp_set_current_user($adminId);

        $controller = new AdminGalleryController();

        \ob_start();
        $controller->renderPage();
        $html = (string) \ob_get_clean();

        self::assertStringContainsString('prox-gallery-admin-root', $html);
    }
}
