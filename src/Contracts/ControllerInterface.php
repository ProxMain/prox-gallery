<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Contracts;

/**
 * Represents an application boundary (admin, REST, AJAX, CLI) that integrates with WordPress.
 */
interface ControllerInterface extends BootableInterface
{
    public function id(): string;
}