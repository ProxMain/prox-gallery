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
        /**
         * Fires after the admin module boots.
         */
        \do_action('prox_gallery/module/admin/booted');
    }
}
