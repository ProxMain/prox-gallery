<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Bootstrap;

use Prox\ProxGallery\Contracts\ManagerInterface;
use Psr\Container\ContainerInterface;

/**
 * Application composition root.
 */
final class App
{
    private Container $container;
    private AppBindingRegistrar $bindingRegistrar;
    private AppManagerRegistrar $managerRegistrar;

    /**
     * @var array<string, ManagerInterface>
     */
    private array $managers = [];

    private function __construct(
        Container $container,
        AppBindingRegistrar $bindingRegistrar,
        AppManagerRegistrar $managerRegistrar
    ) {
        $this->container = $container;
        $this->bindingRegistrar = $bindingRegistrar;
        $this->managerRegistrar = $managerRegistrar;
    }

    /**
     * Creates a new application instance.
     */
    public static function make(): self
    {
        $developmentSeedEnabled = self::developmentSeedEnabled();

        return new self(
            new Container(),
            new AppBindingRegistrar($developmentSeedEnabled),
            new AppManagerRegistrar($developmentSeedEnabled)
        );
    }

    /**
     * Boots the application.
     */
    public function boot(): void
    {
        $this->registerBindings();
        $this->registerManagers();
        $this->bootManagers();
    }

    /**
     * Registers container bindings.
     */
    private function registerBindings(): void
    {
        $this->bindingRegistrar->register($this->container);
    }

    /**
     * Registers manager instances.
     */
    private function registerManagers(): void
    {
        $this->managerRegistrar->register(
            $this->container,
            function (ManagerInterface $manager): void {
                $this->addManager($manager);
            }
        );
    }

    private static function developmentSeedEnabled(): bool
    {
        return \defined('PROX_GALLERY_ENABLE_DEV_SEED_MODULE') && (bool) \PROX_GALLERY_ENABLE_DEV_SEED_MODULE;
    }

    /**
     * Boots all registered managers.
     */
    private function bootManagers(): void
    {
        foreach ($this->managers as $manager) {
            $manager->boot();
        }
    }

    /**
     * Adds a manager to the lifecycle.
     */
    private function addManager(ManagerInterface $manager): void
    {
        $this->managers[$manager->id()] = $manager;
    }

    /**
     * Returns the container.
     */
    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
