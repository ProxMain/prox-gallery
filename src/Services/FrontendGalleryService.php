<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Services;

use Prox\ProxGallery\Contracts\ServiceInterface;
use Prox\ProxGallery\Models\GalleryModel;
use Prox\ProxGallery\Policies\FrontendVisibilityPolicy;
use Prox\ProxGallery\States\FrontendGalleryState;

/**
 * Frontend gallery service.
 */
final class FrontendGalleryService implements ServiceInterface
{
    public function __construct(
        private FrontendGalleryState $state,
        private FrontendVisibilityPolicy $policy,
        private GalleryModel $model
    ) {
    }

    public function id(): string
    {
        return 'frontend.gallery';
    }

    public function boot(): void
    {
        \do_action(
            'prox_gallery/service/frontend_gallery/booted',
            $this->state,
            $this->policy,
            $this->model
        );
    }
}
