<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules;

use Prox\ProxGallery\Contracts\ModuleInterface;

/**
 * Frontend module boundary.
 */
final class FrontendModule implements ModuleInterface
{
    public function id(): string
    {
        return 'frontend';
    }

    public function boot(): void
    {
        \do_action('prox_gallery/module/frontend/booted');
    }
}
