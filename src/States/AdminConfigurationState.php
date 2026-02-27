<?php

declare(strict_types=1);

namespace Prox\ProxGallery\States;

use Prox\ProxGallery\Contracts\StateInterface;

/**
 * Admin configuration state.
 */
final class AdminConfigurationState implements StateInterface
{
    public function id(): string
    {
        return 'admin.configuration';
    }

    public function boot(): void
    {
        /** @param self $state */
        \do_action('prox_gallery/state/admin_configuration/booted', $this);
    }

    public function optionKey(): string
    {
        return 'prox_gallery_options';
    }
}
