<?php

declare(strict_types=1);

use Prox\ProxGallery\Bootstrap\App;
use Prox\ProxGallery\Bootstrap\Plugin;
use Prox\ProxGallery\Managers\CliManager;
use Prox\ProxGallery\Managers\ControllerManager;
use Prox\ProxGallery\Managers\FlowManager;
use Prox\ProxGallery\Managers\ModuleManager;

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
        self::assertTrue($bootedApp->container()->has(ModuleManager::class));
        self::assertTrue($bootedApp->container()->has(FlowManager::class));
        self::assertTrue($bootedApp->container()->has(ControllerManager::class));
        self::assertTrue($bootedApp->container()->has(CliManager::class));
    }

    public function test_it_registers_plugins_loaded_hook_only_once(): void
    {
        remove_action('plugins_loaded', [Plugin::class, 'onPluginsLoaded']);
        $this->setPluginBooted(false);

        Plugin::boot();
        Plugin::boot();

        self::assertSame(
            1,
            $this->countHookCallbacks('plugins_loaded', [Plugin::class, 'onPluginsLoaded'])
        );
    }

    /**
     * @param callable|string|array{0: object|string, 1: string} $target
     */
    private function countHookCallbacks(string $hookName, $target): int
    {
        global $wp_filter;

        if (! isset($wp_filter[$hookName])) {
            return 0;
        }

        $count = 0;

        foreach ($wp_filter[$hookName]->callbacks as $priorityCallbacks) {
            foreach ($priorityCallbacks as $callback) {
                if ($callback['function'] === $target) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function setPluginBooted(bool $value): void
    {
        $property = new ReflectionProperty(Plugin::class, 'booted');
        $property->setAccessible(true);
        $property->setValue($value);
    }
}
