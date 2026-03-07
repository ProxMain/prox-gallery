<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Frontend\Controllers;

use Prox\ProxGallery\Contracts\ControllerInterface;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendGalleryService;
use Prox\ProxGallery\Modules\Frontend\Services\FrontendTrackingService;

/**
 * Frontend boundary controller.
 */
final class FrontendGalleryController implements ControllerInterface
{
    private const TRACK_ACTION = 'prox_gallery_track_event';

    public function __construct(
        private FrontendGalleryService $service,
        private FrontendTrackingService $tracking
    )
    {
    }

    public function id(): string
    {
        return 'frontend.gallery';
    }

    public function boot(): void
    {
        \add_action('wp_ajax_' . self::TRACK_ACTION, [$this, 'trackEvent']);
        \add_action('wp_ajax_nopriv_' . self::TRACK_ACTION, [$this, 'trackEvent']);

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

    /**
     * @return void
     */
    public function trackEvent(): void
    {
        $eventType = isset($_POST['event_type']) ? (string) $_POST['event_type'] : '';
        $galleryId = isset($_POST['gallery_id']) ? (int) $_POST['gallery_id'] : 0;
        $imageId = isset($_POST['image_id']) ? (int) $_POST['image_id'] : 0;

        if ($eventType === 'gallery_visit') {
            $this->tracking->recordGalleryVisit($galleryId);
            \wp_send_json_success(['tracked' => true]);
        }

        if ($eventType === 'image_view') {
            $this->tracking->recordImageView($galleryId, $imageId);
            \wp_send_json_success(['tracked' => true]);
        }

        \wp_send_json_error(['message' => 'Invalid tracking event.'], 400);
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

        \wp_localize_script(
            'prox-gallery-frontend',
            'proxGalleryTracking',
            [
                'ajaxUrl' => \admin_url('admin-ajax.php'),
                'action' => self::TRACK_ACTION,
            ]
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
