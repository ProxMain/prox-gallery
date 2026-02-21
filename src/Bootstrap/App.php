<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Bootstrap;

use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Application composition root.
 *
 * Responsible for:
 * - creating and owning the dependency injection container
 * - binding all services and managers
 * - bootstrapping the application modules
 *
 * This class contains no WordPress integration logic.
 */
final class App
{
    private ContainerInterface $container;

    private function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Creates the application instance.
     *
     * This is the only place where the container is instantiated.
     */
    public static function make(): self
    {
        $container = new class implements ContainerInterface {
            public function get(string $id): mixed
            {
                throw new RuntimeException('Container not configured: ' . $id);
            }

            public function has(string $id): bool
            {
                return false;
            }
        };

        return new self($container);
    }

    /**
     * Boots the application.
     *
     * The boot process is intentionally split into clear phases
     * to keep responsibilities explicit and traceable.
     */
    public function boot(): void
    {
        $this->registerBindings();
        $this->registerManagers();
        $this->bootModules();
    }

    /**
     * Registers all container bindings.
     */
    private function registerBindings(): void
    {
    }

    /**
     * Registers manager classes responsible for coordinating subsystems.
     */
    private function registerManagers(): void
    {
    }

    /**
     * Boots all application modules.
     */
    private function bootModules(): void
    {
    }

    /**
     * Provides access to the container.
     */
    public function container(): ContainerInterface
    {
        return $this->container;
    }
}