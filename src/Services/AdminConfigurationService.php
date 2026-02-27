<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Services;

use Prox\ProxGallery\Contracts\ServiceInterface;
use Prox\ProxGallery\Policies\AdminCapabilityPolicy;
use Prox\ProxGallery\States\AdminConfigurationState;

/**
 * Admin configuration service.
 */
final class AdminConfigurationService implements ServiceInterface
{
    public function __construct(
        private AdminConfigurationState $state,
        private AdminCapabilityPolicy $policy
    ) {
    }

    public function id(): string
    {
        return 'admin.configuration';
    }

    public function boot(): void
    {
        \do_action('prox_gallery/service/admin_configuration/booted', $this->state, $this->policy);
    }
}
