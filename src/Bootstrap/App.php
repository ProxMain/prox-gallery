<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Bootstrap;

use Prox\ProxGallery\Contracts\ManagerInterface;
use Prox\ProxGallery\Managers\CliManager;
use Prox\ProxGallery\Managers\ControllerManager;
use Prox\ProxGallery\Managers\ModuleManager;
use Psr\Container\ContainerInterface;

/**
 * Application composition root.
 *
 * Orchestrates the full application lifecycle by:
 * - creating and owning the dependency injection container
 * - registering all service bindings
 * - registering all managers
 * - booting the manager layer
 *
 * This class contains no WordPress integration logic and can be executed
 * in any runtime context (web, CLI, tests).
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
     * Creates the application instance.
     *
     * This is the single entry point where the container is instantiated.
     */
    public static function make(): self
    {
        return new self(new Container());
    }

    /**
     * Boots the application.
     *
     * The boot process is intentionally divided into explicit phases
     * to make the lifecycle predictable and traceable.
     */
    public function boot(): void
    {
        $this->registerBindings();
        $this->registerManagers();
        $this->bootManagers();
    }

    /**
     * Registers all container bindings.
     *
     * This is where interfaces are mapped to concrete implementations.
     */
    private function registerBindings(): void
    {
        $this->container->set(ModuleManager::class, static fn (): ModuleManager => new ModuleManager());
        $this->container->set(ControllerManager::class, static fn (): ControllerManager => new ControllerManager());
        $this->container->set(CliManager::class, static fn (): CliManager => new CliManager());
    }

    /**
     * Registers all manager instances.
     *
     * Managers act as orchestration layers for modules, controllers and CLI.
     */
    private function registerManagers(): void
    {
        $this->addManager($this->container->get(ControllerManager::class));
        $this->addManager($this->container->get(ModuleManager::class));
        $this->addManager($this->container->get(CliManager::class));
    }

    /**
     * Boots all registered managers.
     *
     * The manager layer controls the boot order of the application.
     */
    private function bootManagers(): void
    {
        foreach ($this->managers as $manager) {
            $manager->boot();
        }
    }

    /**
     * Adds a manager to the application lifecycle.
     */
    private function addManager(ManagerInterface $manager): void
    {
        $this->managers[$manager->id()] = $manager;
    }

    /**
     * Provides access to the container.
     */
    public function container(): ContainerInterface
    {
        return $this->container;
    }
}