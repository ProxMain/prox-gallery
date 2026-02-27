<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Contracts;

/**
 * Represents an application service.
 */
interface ServiceInterface extends BootableInterface
{
    public function id(): string;
}
