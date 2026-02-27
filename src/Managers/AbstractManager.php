<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Managers;

use Prox\ProxGallery\Contracts\ManagerInterface;

/**
 * Base manager with an idempotent boot lifecycle.
 */
abstract class AbstractManager implements ManagerInterface
{
    private bool $booted = false;

    final public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        $this->register();
    }

    abstract public function id(): string;

    abstract protected function register(): void;
}
