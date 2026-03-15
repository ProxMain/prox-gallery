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
    ) {
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
        $nonce = $this->postString('_ajax_nonce');

        if ($nonce === '' || ! \wp_verify_nonce($nonce, self::TRACK_ACTION)) {
            \wp_send_json_error(['message' => 'Nonce verification failed.'], 403);
        }

        $eventType = \sanitize_key($this->postString('event_type'));
        $galleryId = $this->postInt('gallery_id');
        $imageId = $this->postInt('image_id');
        $context = [
            'referrer' => $this->postString('referrer'),
            'current_url' => $this->postString('current_url'),
            'device_type' => $this->postString('device_type'),
        ];

        if ($this->isRateLimited()) {
            \wp_send_json_error(['message' => 'Rate limit exceeded.'], 429);
        }

        if ($eventType === 'gallery_visit') {
            if (! $this->service->galleryExists($galleryId)) {
                \wp_send_json_error(['message' => 'Invalid gallery.'], 400);
            }

            $this->tracking->recordGalleryVisitWithContext($galleryId, $context);
            \wp_send_json_success(['tracked' => true]);
        }

        if ($eventType === 'image_view') {
            if (! $this->imageExists($imageId)) {
                \wp_send_json_error(['message' => 'Invalid image.'], 400);
            }

            if ($galleryId > 0 && ! $this->service->galleryContainsImage($galleryId, $imageId)) {
                \wp_send_json_error(['message' => 'Image is not part of this gallery.'], 400);
            }

            $this->tracking->recordImageViewWithContext($galleryId, $imageId, $context);
            \wp_send_json_success(['tracked' => true]);
        }

        if ($eventType === 'lightbox_open') {
            if (! $this->imageExists($imageId)) {
                \wp_send_json_error(['message' => 'Invalid image.'], 400);
            }

            if ($galleryId > 0 && ! $this->service->galleryContainsImage($galleryId, $imageId)) {
                \wp_send_json_error(['message' => 'Image is not part of this gallery.'], 400);
            }

            $this->tracking->recordLightboxOpen($galleryId, $imageId, $context);
            \wp_send_json_success(['tracked' => true]);
        }

        if ($eventType === 'info_panel_open') {
            if (! $this->imageExists($imageId)) {
                \wp_send_json_error(['message' => 'Invalid image.'], 400);
            }

            if ($galleryId > 0 && ! $this->service->galleryContainsImage($galleryId, $imageId)) {
                \wp_send_json_error(['message' => 'Image is not part of this gallery.'], 400);
            }

            $this->tracking->recordInfoPanelOpen($galleryId, $imageId, $context);
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
                'nonce' => \wp_create_nonce(self::TRACK_ACTION),
            ]
        );
    }

    private function imageExists(int $imageId): bool
    {
        if ($imageId <= 0) {
            return false;
        }

        $post = \get_post($imageId);

        if (! $post instanceof \WP_Post || $post->post_type !== 'attachment') {
            return false;
        }

        return (bool) \wp_attachment_is_image($imageId);
    }

    private function postString(string $key): string
    {
        $value = \filter_input(\INPUT_POST, $key, \FILTER_UNSAFE_RAW, \FILTER_NULL_ON_FAILURE);

        if (! is_string($value)) {
            return '';
        }

        return (string) \wp_unslash($value);
    }

    private function postInt(string $key): int
    {
        return (int) $this->postString($key);
    }

    private function isRateLimited(): bool
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $ip = trim($ip);

        if ($ip === '') {
            $ip = 'unknown';
        }

        $bucket = \gmdate('YmdHi');
        $key = 'prox_gallery_track_rl_' . md5($ip . '|' . $bucket);
        $count = (int) \get_transient($key);
        $count += 1;
        \set_transient($key, $count, 70);

        return $count > 120;
    }

    private function isFrontendRequest(): bool
    {
        if (! \function_exists('is_admin')) {
            return true;
        }

        return ! \is_admin();
    }
}
