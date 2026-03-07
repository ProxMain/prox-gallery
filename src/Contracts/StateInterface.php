<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Contracts;

/**
 * Represents mutable application state.
 */
interface StateInterface
{
    public function id(): string;
}
