<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Contracts;

/**
 * Represents a cohesive feature module of the application.
 */
interface ModuleInterface extends BootableInterface
{
    public function id(): string;
}