<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Admin\Controllers;

use Prox\ProxGallery\Contracts\ControllerInterface;
use Prox\ProxGallery\Controllers\Admin\AdminAssetLoader;
use Prox\ProxGallery\Controllers\Admin\AdminConfigProvider;
use Prox\ProxGallery\Controllers\Admin\AdminMenuRegistrar;

/**
 * Admin boundary controller.
 */
final class AdminGalleryController implements ControllerInterface
{
    private string $screenHookSuffix = '';

    public function __construct(
        private AdminMenuRegistrar $menuRegistrar,
        private AdminAssetLoader $assetLoader,
        private AdminConfigProvider $configProvider
    ) {
    }

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
        \add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        \add_filter('script_loader_tag', [$this, 'filterModuleScriptTag'], 10, 3);
    }

    public function registerMenu(): void
    {
        $this->screenHookSuffix = $this->menuRegistrar->register(
            [$this, 'renderPage'],
            $this->canManage()
        );

        /**
         * Fires when the plugin admin menu should be registered.
         */
        \do_action('prox_gallery/admin/menu/register', $this->screenHookSuffix);
    }

    public function renderPage(): void
    {
        $this->menuRegistrar->render($this->canManage());
    }

    public function enqueueAdminAssets(string $hookSuffix): void
    {
        $this->assetLoader->enqueue(
            $this->screenHookSuffix,
            $hookSuffix,
            $this->adminConfigPayload()
        );
    }

    private function canManage(): bool
    {
        return (bool) \apply_filters('prox_gallery/admin/can_manage', true);
    }

    /**
     * @return array{
     *     screen:string,
     *     rest_nonce:string,
     *     ajax_url:string
     * }
     */
    private function adminConfigPayload(): array
    {
        return $this->configProvider->payload($this->screenHookSuffix);
    }

    public function filterModuleScriptTag(string $tag, string $handle, string $src): string
    {
        return $this->assetLoader->filterModuleScriptTag($tag, $handle, $src);
    }

    private function isAdminRequest(): bool
    {
        return \function_exists('is_admin') && \is_admin();
    }
}
