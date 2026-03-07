<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Contracts;

/**
 * Represents a policy boundary.
 */
interface PolicyInterface
{
    public function id(): string;
}
