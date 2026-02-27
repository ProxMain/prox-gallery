<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers;

use Prox\ProxGallery\Contracts\ControllerInterface;

/**
 * Frontend boundary controller.
 */
final class FrontendGalleryController implements ControllerInterface
{
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
        /**
         * Fires after the frontend shortcode handler runs.
         *
         * @param array<string, mixed> $attributes Shortcode attributes.
         * @param string               $content    Enclosed content.
         * @param string               $tag        Triggering shortcode tag.
         */
        \do_action('prox_gallery/frontend/shortcode/rendered', $attributes, $content, $tag);

        return '';
    }

    private function isFrontendRequest(): bool
    {
        if (! \function_exists('is_admin')) {
            return true;
        }

        return ! \is_admin();
    }
}
