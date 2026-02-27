<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Policies;

use Prox\ProxGallery\Contracts\PolicyInterface;

/**
 * Admin capability policy.
 */
final class AdminCapabilityPolicy implements PolicyInterface
{
    public function id(): string
    {
        return 'admin.capability';
    }

    public function boot(): void
    {
        \add_filter('prox_gallery/admin/can_manage', [$this, 'canManage']);
    }

    public function canManage(bool $allowed = true): bool
    {
        if (! \function_exists('current_user_can')) {
            return false;
        }

        return $allowed && \current_user_can('manage_options');
    }
}
