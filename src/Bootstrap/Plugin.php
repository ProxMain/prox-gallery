<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Bootstrap;

/**
 * WordPress plugin bootstrapper.
 */
final class Plugin
{
    private static bool $booted = false;

    /**
     * Registers WordPress hooks.
     */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        add_action('plugins_loaded', [self::class, 'onPluginsLoaded']);
    }

    /**
     * Boots the application after all plugins are loaded.
     */
    public static function onPluginsLoaded(): void
    {
        $app = App::make();
        $app->boot();

        /**
         * Fires after the application has booted.
         *
         * @param App $app Application instance.
         */
        do_action('prox_gallery/booted', $app);
    }
}
