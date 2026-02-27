<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Contracts;

/**
 * Represents a request flow boundary.
 */
interface FlowInterface extends BootableInterface
{
    public function id(): string;
}
