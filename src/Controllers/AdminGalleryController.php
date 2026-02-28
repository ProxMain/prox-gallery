<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers;

use Prox\ProxGallery\Contracts\ControllerInterface;

/**
 * Admin boundary controller.
 */
final class AdminGalleryController implements ControllerInterface
{
    private const MENU_SLUG = 'prox-gallery';
    private const SCRIPT_HANDLE = 'prox-gallery-admin-app';
    private const DEV_CLIENT_HANDLE = 'prox-gallery-admin-vite-client';
    private const DEV_ENTRY_HANDLE = 'prox-gallery-admin-vite-entry';
    private const STYLE_HANDLE = 'prox-gallery-admin-app';
    private const ENTRYPOINT = 'assets/admin/src/main.jsx';
    /**
     * @var list<string>
     */
    private const MODULE_SCRIPT_HANDLES = [
        self::SCRIPT_HANDLE,
        self::DEV_CLIENT_HANDLE,
        self::DEV_ENTRY_HANDLE,
    ];

    private string $screenHookSuffix = '';

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
        $hookSuffix = \add_menu_page(
            'Prox Gallery',
            'Prox Gallery',
            $this->requiredCapability(),
            self::MENU_SLUG,
            [$this, 'renderPage'],
            'dashicons-format-gallery',
            58
        );

        if (is_string($hookSuffix)) {
            $this->screenHookSuffix = $hookSuffix;
        }

        /**
         * Fires when the plugin admin menu should be registered.
         */
        \do_action('prox_gallery/admin/menu/register', $this->screenHookSuffix);
    }

    public function renderPage(): void
    {
        if (! $this->canManage()) {
            \wp_die(
                \esc_html__('You do not have permission to access this page.', 'prox-gallery')
            );
        }

        echo '<div class="wrap prox-gallery-admin-wrap">';
        echo '<div id="prox-gallery-admin-root"></div>';
        echo '</div>';
    }

    public function enqueueAdminAssets(string $hookSuffix): void
    {
        if ($this->screenHookSuffix === '' || $hookSuffix !== $this->screenHookSuffix) {
            return;
        }

        if ($this->isDevServerEnabled()) {
            $devServer = rtrim($this->devServerUrl(), '/');

            \wp_enqueue_script(
                self::DEV_CLIENT_HANDLE,
                $devServer . '/@vite/client',
                [],
                null,
                [
                    'strategy' => 'defer',
                    'in_footer' => true,
                ]
            );
            \wp_script_add_data(self::DEV_CLIENT_HANDLE, 'type', 'module');

            \wp_enqueue_script(
                self::DEV_ENTRY_HANDLE,
                $devServer . '/assets/admin/src/main.jsx',
                [],
                null,
                [
                    'strategy' => 'defer',
                    'in_footer' => true,
                ]
            );
            \wp_script_add_data(self::DEV_ENTRY_HANDLE, 'type', 'module');
            \wp_add_inline_script(
                self::DEV_ENTRY_HANDLE,
                sprintf(
                    'window.ProxGalleryAdminConfig = %s;',
                    \wp_json_encode($this->adminConfigPayload())
                ),
                'before'
            );

            return;
        }

        $assets = $this->resolveBuildAssets();

        if ($assets === null) {
            return;
        }

        foreach ($assets['styles'] as $index => $relativePath) {
            \wp_enqueue_style(
                self::STYLE_HANDLE . '-' . $index,
                \plugins_url($relativePath, PROX_GALLERY_FILE),
                [],
                $this->assetVersion($relativePath)
            );
        }

        \wp_enqueue_script(
            self::SCRIPT_HANDLE,
            \plugins_url($assets['script'], PROX_GALLERY_FILE),
            [],
            $this->assetVersion($assets['script']),
            [
                'strategy' => 'defer',
                'in_footer' => true,
            ]
        );
        \wp_script_add_data(self::SCRIPT_HANDLE, 'type', 'module');
        \wp_add_inline_script(
            self::SCRIPT_HANDLE,
            sprintf(
                'window.ProxGalleryAdminConfig = %s;',
                \wp_json_encode($this->adminConfigPayload())
            ),
            'before'
        );
    }

    private function canManage(): bool
    {
        return (bool) \apply_filters('prox_gallery/admin/can_manage', true);
    }

    private function requiredCapability(): string
    {
        return $this->canManage() ? 'manage_options' : 'do_not_allow';
    }

    /**
     * @return array{script:string, styles:list<string>}|null
     */
    private function resolveBuildAssets(): ?array
    {
        $manifestPath = PROX_GALLERY_DIR . '/assets/admin/build/manifest.json';

        if (! \is_readable($manifestPath)) {
            return null;
        }

        $json = \file_get_contents($manifestPath);

        if (! is_string($json) || $json === '') {
            return null;
        }

        $manifest = \json_decode($json, true);

        if (! is_array($manifest)) {
            return null;
        }

        $entry = $manifest[self::ENTRYPOINT] ?? null;

        if (! is_array($entry)) {
            return null;
        }

        $file = isset($entry['file']) ? (string) $entry['file'] : '';

        if ($file === '') {
            return null;
        }

        $styles = [];
        $entryStyles = $entry['css'] ?? [];

        if (is_array($entryStyles)) {
            foreach ($entryStyles as $style) {
                if (! is_string($style) || $style === '') {
                    continue;
                }

                $styles[] = 'assets/admin/build/' . ltrim($style, '/');
            }
        }

        return [
            'script' => 'assets/admin/build/' . ltrim($file, '/'),
            'styles' => $styles,
        ];
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
        $payload = [
            'screen' => $this->screenHookSuffix,
            'rest_nonce' => (string) \wp_create_nonce('wp_rest'),
            'ajax_url' => (string) \admin_url('admin-ajax.php'),
        ];

        /**
         * Filters payload passed to the Prox Gallery admin app bootstrap.
         *
         * @param array{
         *     screen:string,
         *     rest_nonce:string,
         *     ajax_url:string
         * } $payload
         */
        $filtered = \apply_filters('prox_gallery/admin/config_payload', $payload);

        return is_array($filtered) ? $filtered : $payload;
    }

    private function assetVersion(string $relativePath): string
    {
        $path = PROX_GALLERY_DIR . '/' . ltrim($relativePath, '/');

        if (! \is_readable($path)) {
            return (string) \time();
        }

        $modified = \filemtime($path);

        if ($modified === false) {
            return (string) \time();
        }

        return (string) $modified;
    }

    public function filterModuleScriptTag(string $tag, string $handle, string $src): string
    {
        if (! in_array($handle, self::MODULE_SCRIPT_HANDLES, true)) {
            return $tag;
        }

        return sprintf(
            '<script type="module" src="%s" id="%s-js"></script>',
            \esc_url($src),
            \esc_attr($handle)
        );
    }

    private function isDevServerEnabled(): bool
    {
        $default = \defined('WP_DEBUG') && \WP_DEBUG;

        return (bool) \apply_filters('prox_gallery/admin/vite_dev_server/enabled', $default);
    }

    private function devServerUrl(): string
    {
        $default = 'http://localhost:5173';

        return (string) \apply_filters('prox_gallery/admin/vite_dev_server/url', $default);
    }

    private function isAdminRequest(): bool
    {
        return \function_exists('is_admin') && \is_admin();
    }
}
