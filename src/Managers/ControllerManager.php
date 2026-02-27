<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Managers;

use Prox\ProxGallery\Contracts\ControllerInterface;

/**
 * Boots registered controllers that integrate with WordPress boundaries.
 */
final class ControllerManager extends AbstractManager
{
    /**
     * @var array<string, ControllerInterface>
     */
    private array $controllers = [];

    public function id(): string
    {
        return 'controllers';
    }

    public function add(ControllerInterface $controller): void
    {
        $this->controllers[$controller->id()] = $controller;
    }

    protected function register(): void
    {
        foreach ($this->controllers as $controller) {
            $controller->boot();
        }
    }
}