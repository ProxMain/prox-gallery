<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers\Admin;

/**
 * Loads admin scripts/styles for dev and build modes.
 */
final class AdminAssetLoader
{
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
    private bool $buildAssetIssueReported = false;

    /**
     * @param array<string, mixed> $adminConfigPayload
     */
    public function enqueue(string $screenHookSuffix, string $hookSuffix, array $adminConfigPayload): void
    {
        if ($screenHookSuffix === '' || $hookSuffix !== $screenHookSuffix) {
            return;
        }

        if ($this->isDevServerEnabled()) {
            $this->enqueueDevAssets($adminConfigPayload);

            return;
        }

        $this->enqueueBuildAssets($adminConfigPayload);
    }

    public function filterModuleScriptTag(string $tag, string $handle, string $src): string
    {
        if (! in_array($handle, self::MODULE_SCRIPT_HANDLES, true)) {
            return $tag;
        }

        $scriptId = \preg_quote($handle . '-js', '/');

        return (string) \preg_replace_callback(
            '/<script\b([^>]*\bid=(["\'])' . $scriptId . '\2[^>]*)>/i',
            static function (array $matches): string {
                $attributes = $matches[1] ?? '';

                if (\preg_match('/\btype=(["\'])module\1/i', $attributes) === 1) {
                    return (string) ($matches[0] ?? '');
                }

                return '<script type="module"' . $attributes . '>';
            },
            $tag,
            1
        );
    }

    /**
     * @param array<string, mixed> $adminConfigPayload
     */
    private function enqueueDevAssets(array $adminConfigPayload): void
    {
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
                \wp_json_encode($adminConfigPayload)
            ),
            'before'
        );
    }

    /**
     * @param array<string, mixed> $adminConfigPayload
     */
    private function enqueueBuildAssets(array $adminConfigPayload): void
    {
        $assets = $this->resolveBuildAssets();

        if ($assets === null) {
            $this->reportBuildAssetIssue(
                'Unable to load admin build assets from assets/admin/build/manifest.json. Run the admin build.'
            );
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
                \wp_json_encode($adminConfigPayload)
            ),
            'before'
        );
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

    private function reportBuildAssetIssue(string $message): void
    {
        if ($this->buildAssetIssueReported) {
            return;
        }

        $this->buildAssetIssueReported = true;
        \error_log('[prox-gallery] ' . $message);

        \add_action(
            'admin_notices',
            static function () use ($message): void {
                echo '<div class="notice notice-error"><p>' . \esc_html($message) . '</p></div>';
            }
        );
    }
}
