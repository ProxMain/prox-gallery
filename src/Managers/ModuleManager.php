<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Managers;

use Prox\ProxGallery\Contracts\ModuleInterface;

/**
 * Boots registered feature modules.
 */
final class ModuleManager extends AbstractManager
{
    /**
     * @var array<string, ModuleInterface>
     */
    private array $modules = [];

    public function id(): string
    {
        return 'modules';
    }

    public function add(ModuleInterface $module): void
    {
        $this->modules[$module->id()] = $module;
    }

    protected function register(): void
    {
        foreach ($this->modules as $module) {
            $module->boot();
        }
    }
}