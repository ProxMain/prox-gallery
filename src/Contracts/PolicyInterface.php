<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Contracts;

/**
 * Represents a policy boundary.
 */
interface PolicyInterface extends BootableInterface
{
    public function id(): string;
}
