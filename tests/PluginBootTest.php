<?php

declare(strict_types=1);

use Prox\ProxGallery\Bootstrap\App;
use Prox\ProxGallery\Bootstrap\Plugin;

final class PluginBootTest extends WP_UnitTestCase
{
    public function test_it_boots_the_application_on_plugins_loaded(): void
    {
        $bootedApp = null;

        add_action(
            'prox_gallery/booted',
            static function (App $app) use (&$bootedApp): void {
                $bootedApp = $app;
            },
            10,
            1
        );

        Plugin::boot();

        do_action('plugins_loaded');

        $this->assertInstanceOf(App::class, $bootedApp);
    }
}