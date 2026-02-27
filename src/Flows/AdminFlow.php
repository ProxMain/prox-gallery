<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Flows;

use Prox\ProxGallery\Contracts\FlowInterface;
use Prox\ProxGallery\Policies\AdminCapabilityPolicy;
use Prox\ProxGallery\Services\AdminConfigurationService;
use Prox\ProxGallery\States\AdminConfigurationState;

/**
 * Admin request flow.
 */
final class AdminFlow implements FlowInterface
{
    public function __construct(
        private AdminCapabilityPolicy $policy,
        private AdminConfigurationState $state,
        private AdminConfigurationService $service
    ) {
    }

    public function id(): string
    {
        return 'admin';
    }

    public function boot(): void
    {
        if (! $this->isAdminRequest()) {
            return;
        }

        $this->policy->boot();
        $this->state->boot();
        $this->service->boot();

        \do_action('prox_gallery/flow/admin/booted', $this);
    }

    private function isAdminRequest(): bool
    {
        return \function_exists('is_admin') && \is_admin();
    }
}
