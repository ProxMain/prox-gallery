<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Flows;

use Prox\ProxGallery\Contracts\FlowInterface;
use Prox\ProxGallery\Policies\FrontendVisibilityPolicy;
use Prox\ProxGallery\Services\FrontendGalleryService;
use Prox\ProxGallery\States\FrontendGalleryState;

/**
 * Frontend request flow.
 */
final class FrontendFlow implements FlowInterface
{
    public function __construct(
        private FrontendVisibilityPolicy $policy,
        private FrontendGalleryState $state,
        private FrontendGalleryService $service
    ) {
    }

    public function id(): string
    {
        return 'frontend';
    }

    public function boot(): void
    {
        if (! $this->isFrontendRequest()) {
            return;
        }

        $this->policy->boot();
        $this->state->boot();
        $this->service->boot();

        /** @param self $flow */
        \do_action('prox_gallery/flow/frontend/booted', $this);
    }

    private function isFrontendRequest(): bool
    {
        if (! \function_exists('is_admin')) {
            return true;
        }

        return ! \is_admin();
    }
}
