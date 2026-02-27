<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers;

use Prox\ProxGallery\Contracts\ControllerInterface;

/**
 * Admin boundary controller.
 */
final class AdminGalleryController implements ControllerInterface
{
    public function id(): string
    {
        return 'admin.gallery';
    }

    public function boot(): void
    {
        if (! $this->isAdminRequest()) {
            return;
        }

        \add_action('admin_menu', [$this, 'registerMenu']);
    }

    public function registerMenu(): void
    {
        \do_action('prox_gallery/admin/menu/register');
    }

    private function isAdminRequest(): bool
    {
        return \function_exists('is_admin') && \is_admin();
    }
}
