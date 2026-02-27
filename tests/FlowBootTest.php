<?php

declare(strict_types=1);

use Prox\ProxGallery\Bootstrap\App;

final class FlowBootTest extends WP_UnitTestCase
{
    public function test_it_boots_frontend_flow_on_frontend_requests(): void
    {
        $this->switchToFrontendScreen();

        $beforeFrontend = \did_action('prox_gallery/flow/frontend/booted');
        $beforeAdmin = \did_action('prox_gallery/flow/admin/booted');

        App::make()->boot();

        self::assertSame($beforeFrontend + 1, \did_action('prox_gallery/flow/frontend/booted'));
        self::assertSame($beforeAdmin, \did_action('prox_gallery/flow/admin/booted'));
    }

    public function test_it_boots_admin_flow_on_admin_requests(): void
    {
        if (! \function_exists('set_current_screen')) {
            $this->markTestSkipped('set_current_screen is not available.');
        }

        \set_current_screen('dashboard');

        $beforeFrontend = \did_action('prox_gallery/flow/frontend/booted');
        $beforeAdmin = \did_action('prox_gallery/flow/admin/booted');

        App::make()->boot();

        self::assertSame($beforeAdmin + 1, \did_action('prox_gallery/flow/admin/booted'));
        self::assertSame($beforeFrontend, \did_action('prox_gallery/flow/frontend/booted'));
    }

    private function switchToFrontendScreen(): void
    {
        if (! \function_exists('set_current_screen')) {
            return;
        }

        \set_current_screen('front');
    }
}
