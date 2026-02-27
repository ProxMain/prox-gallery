<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary;

use Prox\ProxGallery\Contracts\ModuleInterface;
use Prox\ProxGallery\Modules\MediaLibrary\Services\TrackUploadedImageService;

/**
 * Media library feature module.
 */
final class MediaLibraryModule implements ModuleInterface
{
    public function __construct(private TrackUploadedImageService $service)
    {
    }

    public function id(): string
    {
        return 'media_library';
    }

    public function boot(): void
    {
        $this->service->boot();

        /**
         * Fires after the media library module boots.
         */
        \do_action('prox_gallery/module/media_library/booted');
    }
}
