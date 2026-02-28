<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers;

use Prox\ProxGallery\Contracts\ControllerInterface;
use Prox\ProxGallery\Services\FrontendGalleryService;

/**
 * Frontend boundary controller.
 */
final class FrontendGalleryController implements ControllerInterface
{
    public function __construct(private FrontendGalleryService $service)
    {
    }

    public function id(): string
    {
        return 'frontend.gallery';
    }

    public function boot(): void
    {
        if (! $this->isFrontendRequest()) {
            return;
        }

        \add_shortcode('prox_gallery', [$this, 'renderShortcode']);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function renderShortcode(array $attributes = [], string $content = '', string $tag = ''): string
    {
        $this->enqueueFrontendAssets();
        $html = $this->service->renderShortcode($attributes);

        /**
         * Fires after the frontend shortcode handler runs.
         *
         * @param array<string, mixed> $attributes Shortcode attributes.
         * @param string               $content    Enclosed content.
         * @param string               $tag        Triggering shortcode tag.
         */
        \do_action('prox_gallery/frontend/shortcode/rendered', $attributes, $content, $tag);

        return $html;
    }

    private function enqueueFrontendAssets(): void
    {
        $relativePath = 'assets/frontend/prox-gallery-frontend.css';
        $absolutePath = \trailingslashit(\PROX_GALLERY_DIR) . $relativePath;
        $version = file_exists($absolutePath)
            ? (string) filemtime($absolutePath)
            : '1.0.0';

        \wp_enqueue_style(
            'prox-gallery-frontend',
            \plugins_url($relativePath, \PROX_GALLERY_FILE),
            [],
            $version
        );

        $scriptRelativePath = 'assets/frontend/prox-gallery-frontend.js';
        $scriptAbsolutePath = \trailingslashit(\PROX_GALLERY_DIR) . $scriptRelativePath;
        $scriptVersion = file_exists($scriptAbsolutePath)
            ? (string) filemtime($scriptAbsolutePath)
            : '1.0.0';

        \wp_enqueue_script(
            'prox-gallery-frontend',
            \plugins_url($scriptRelativePath, \PROX_GALLERY_FILE),
            [],
            $scriptVersion,
            true
        );
    }

    private function isFrontendRequest(): bool
    {
        if (! \function_exists('is_admin')) {
            return true;
        }

        return ! \is_admin();
    }
}
