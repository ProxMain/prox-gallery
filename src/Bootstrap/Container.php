<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Bootstrap;

use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Minimal PSR-11 container.
 *
 * Supports:
 * - registering factories
 * - lazy instantiation
 * - singleton-style caching per id
 *
 * The container is intentionally small to keep the foundation lightweight.
 */
final class Container implements ContainerInterface
{
    /**
     * @var array<string, callable(self): mixed>
     */
    private array $factories = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Registers a factory for an identifier.
     *
     * @param callable(self): mixed $factory
     */
    public function set(string $id, callable $factory): void
    {
        unset($this->instances[$id]);
        $this->factories[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (! $this->has($id)) {
            throw new RuntimeException('Container entry not found: ' . $id);
        }

        $this->instances[$id] = ($this->factories[$id])($this);

        return $this->instances[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->factories);
    }
}