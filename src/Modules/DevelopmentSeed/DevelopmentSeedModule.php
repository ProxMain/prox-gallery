<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\DevelopmentSeed;

use Prox\ProxGallery\Contracts\ModuleInterface;
use Prox\ProxGallery\Modules\DevelopmentSeed\Services\DevelopmentSeedService;

/**
 * Module containing development-only seed tooling.
 */
final class DevelopmentSeedModule implements ModuleInterface
{
    public function __construct(private DevelopmentSeedService $service)
    {
    }

    public function id(): string
    {
        return 'development.seed';
    }

    public function boot(): void
    {
        $this->service->boot();

        /**
         * Fires when the development seed module is booted.
         */
        \do_action('prox_gallery/module/development_seed/booted');
    }
}
