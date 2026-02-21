<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Contracts;

/**
 * Orchestrates a subsystem and controls part of the boot order.
 */
interface ManagerInterface extends BootableInterface
{
    public function id(): string;
}