<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Bootstrap;

/**
 * WordPress plugin bootstrapper.
 *
 * Responsible for:
 * - guarding against multiple boots
 * - aligning the application lifecycle with WordPress hooks
 * - triggering the application boot process
 *
 * This class is the only WordPress-facing entry point of the plugin.
 */
final class Plugin
{
    private static bool $booted = false;

    /**
     * Registers the plugin with the WordPress lifecycle.
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
     * Boots the application once all plugins are loaded.
     *
     * At this point:
     * - all dependencies are available
     * - translations can be loaded
     * - services can be wired safely
     */
    public static function onPluginsLoaded(): void
    {
        $app = App::make();
        $app->boot();

        /**
         * Fires after the Prox Gallery application has booted.
         *
         * @param App $app Application instance.
         */
        do_action('prox_gallery/booted', $app);
    }
}
