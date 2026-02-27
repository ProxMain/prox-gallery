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
        /**
         * Fires after the admin configuration service boots.
         *
         * @param AdminConfigurationState $state  Admin state instance.
         * @param AdminCapabilityPolicy   $policy Capability policy instance.
         */
        \do_action('prox_gallery/service/admin_configuration/booted', $this->state, $this->policy);
    }
}
