<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Gallery;

use Prox\ProxGallery\Contracts\ModuleInterface;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryService;

/**
 * Gallery feature module.
 */
final class GalleryModule implements ModuleInterface
{
    public function __construct(private GalleryService $service)
    {
    }

    public function id(): string
    {
        return 'gallery';
    }

    public function boot(): void
    {
        $this->service->boot();

        /**
         * Fires after the gallery module boots.
         */
        \do_action('prox_gallery/module/gallery/booted');
    }
}
