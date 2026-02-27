<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules;

use Prox\ProxGallery\Contracts\ModuleInterface;

/**
 * Core application module.
 */
final class CoreModule implements ModuleInterface
{
    public function id(): string
    {
        return 'core';
    }

    public function boot(): void
    {
        \do_action('prox_gallery/module/core/booted');
    }
}
