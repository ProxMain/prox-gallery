<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Bootstrap;

use Prox\ProxGallery\Contracts\ManagerInterface;
use Prox\ProxGallery\Managers\CliManager;
use Prox\ProxGallery\Managers\ControllerManager;
use Prox\ProxGallery\Managers\ModuleManager;
use Prox\ProxGallery\Modules\CoreModule;
use Psr\Container\ContainerInterface;

/**
 * Application composition root.
 */
final class App
{
    private Container $container;

    /**
     * @var array<string, ManagerInterface>
     */
    private array $managers = [];

    private function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Creates a new application instance.
     */
    public static function make(): self
    {
        return new self(new Container());
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
        $this->container->set(ModuleManager::class, static fn (): ModuleManager => new ModuleManager());
        $this->container->set(ControllerManager::class, static fn (): ControllerManager => new ControllerManager());
        $this->container->set(CliManager::class, static fn (): CliManager => new CliManager());
        $this->container->set(CoreModule::class, static fn () => new CoreModule());
    }

    /**
     * Registers manager instances.
     */
    private function registerManagers(): void
    {
        $moduleManager = $this->container->get(ModuleManager::class);
        $moduleManager->add($this->container->get(CoreModule::class));
        $this->addManager($moduleManager);
        $this->addManager($this->container->get(ControllerManager::class));
        $this->addManager($this->container->get(CliManager::class));
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
