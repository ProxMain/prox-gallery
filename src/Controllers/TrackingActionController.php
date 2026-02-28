<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers;

use Prox\ProxGallery\Modules\Gallery\Services\GalleryService;
use Prox\ProxGallery\Services\FrontendTrackingService;

/**
 * Admin AJAX controller for analytics/tracking summaries.
 */
final class TrackingActionController extends AbstractActionController
{
    private const ACTION_GET = 'prox_gallery_tracking_summary_get';

    public function __construct(
        private FrontendTrackingService $tracking,
        private GalleryService $galleries
    ) {
    }

    public function id(): string
    {
        return 'tracking.actions';
    }

    public function boot(): void
    {
        parent::boot();

        \add_filter('prox_gallery/admin/config_payload', [$this, 'extendAdminConfig']);
    }

    /**
     * @return array<string, array{callback:string, nonce_action?:string, capability?:string}>
     */
    protected function actions(): array
    {
        return [
            self::ACTION_GET => [
                'callback' => 'getSummary',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function getSummary(array $payload, string $action): array
    {
        $stats = $this->tracking->stats();
        $galleryRows = $this->galleries->list();
        $galleryNames = [];

        foreach ($galleryRows as $row) {
            $galleryNames[(string) ((int) ($row['id'] ?? 0))] = (string) ($row['name'] ?? '');
        }

        $galleryItems = [];
        $galleryCountries = [];
        $galleryViewsTotal = 0;

        $rawGalleryStats = isset($stats['galleries']) && is_array($stats['galleries']) ? $stats['galleries'] : [];

        foreach ($rawGalleryStats as $galleryId => $item) {
            if (! is_array($item)) {
                continue;
            }

            $total = isset($item['total']) ? max(0, (int) $item['total']) : 0;
            $countries = isset($item['countries']) && is_array($item['countries']) ? $item['countries'] : [];
            $galleryViewsTotal += $total;

            foreach ($countries as $code => $count) {
                $key = strtoupper((string) $code);
                $galleryCountries[$key] = ($galleryCountries[$key] ?? 0) + max(0, (int) $count);
            }

            $galleryItems[] = [
                'gallery_id' => (int) $galleryId,
                'name' => $galleryNames[(string) $galleryId] ?? ('Gallery #' . (int) $galleryId),
                'total' => $total,
                'countries' => $countries,
            ];
        }

        usort(
            $galleryItems,
            static fn (array $left, array $right): int => ((int) $right['total']) <=> ((int) $left['total'])
        );

        arsort($galleryCountries);

        $imageItems = [];
        $imageViewsTotal = 0;
        $rawImageStats = isset($stats['images']) && is_array($stats['images']) ? $stats['images'] : [];

        foreach ($rawImageStats as $imageId => $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = (int) $imageId;
            if ($id <= 0) {
                continue;
            }

            $total = isset($item['total']) ? max(0, (int) $item['total']) : 0;
            $countries = isset($item['countries']) && is_array($item['countries']) ? $item['countries'] : [];
            $imageViewsTotal += $total;

            $imageItems[] = [
                'image_id' => $id,
                'title' => $this->imageTitle($id),
                'total' => $total,
                'countries' => $countries,
            ];
        }

        usort(
            $imageItems,
            static fn (array $left, array $right): int => ((int) $right['total']) <=> ((int) $left['total'])
        );

        return [
            'action' => $action,
            'summary' => [
                'totals' => [
                    'gallery_views' => $galleryViewsTotal,
                    'image_views' => $imageViewsTotal,
                ],
                'countries' => $galleryCountries,
                'galleries' => $galleryItems,
                'images' => $imageItems,
                'updated_at' => isset($stats['updated_at']) ? (string) $stats['updated_at'] : '',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public function extendAdminConfig(array $config): array
    {
        $controllers = [];

        if (isset($config['action_controllers']) && is_array($config['action_controllers'])) {
            $controllers = $config['action_controllers'];
        }

        $controllers['tracking'] = [
            'get' => [
                'action' => self::ACTION_GET,
                'nonce' => \wp_create_nonce(self::ACTION_GET),
            ],
        ];

        $config['action_controllers'] = $controllers;

        return $config;
    }

    private function imageTitle(int $imageId): string
    {
        $title = \get_the_title($imageId);

        if (! is_string($title) || trim($title) === '') {
            return '#' . $imageId;
        }

        return $title;
    }
}

