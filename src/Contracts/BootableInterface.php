<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Contracts;

/**
 * Describes a component that participates in the application lifecycle.
 */
interface BootableInterface
{
    public function boot(): void;
}