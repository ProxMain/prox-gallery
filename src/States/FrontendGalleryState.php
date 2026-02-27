<?php

declare(strict_types=1);

namespace Prox\ProxGallery\States;

use Prox\ProxGallery\Contracts\StateInterface;

/**
 * Frontend gallery state.
 */
final class FrontendGalleryState implements StateInterface
{
    public function id(): string
    {
        return 'frontend.gallery';
    }

    public function boot(): void
    {
        \do_action('prox_gallery/state/frontend_gallery/booted', $this);
    }

    public function defaultLayout(): string
    {
        return 'masonry';
    }
}
