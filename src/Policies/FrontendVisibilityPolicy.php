<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Policies;

use Prox\ProxGallery\Contracts\PolicyInterface;

/**
 * Frontend visibility policy.
 */
final class FrontendVisibilityPolicy implements PolicyInterface
{
    public function id(): string
    {
        return 'frontend.visibility';
    }

    public function boot(): void
    {
        /**
         * Filters whether frontend output is allowed to render.
         *
         * @param bool $allowed Current render decision.
         */
        \add_filter('prox_gallery/frontend/can_render', [$this, 'canRender']);
    }

    public function canRender(bool $allowed = true): bool
    {
        return $allowed;
    }
}
