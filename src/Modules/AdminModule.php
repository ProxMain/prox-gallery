<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules;

use Prox\ProxGallery\Contracts\ModuleInterface;

/**
 * Admin module boundary.
 */
final class AdminModule implements ModuleInterface
{
    public function id(): string
    {
        return 'admin';
    }

    public function boot(): void
    {
        \do_action('prox_gallery/module/admin/booted');
    }
}
