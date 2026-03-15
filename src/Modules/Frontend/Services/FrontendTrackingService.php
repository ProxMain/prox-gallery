<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Frontend\Services;

use Prox\ProxGallery\Contracts\ServiceInterface;

/**
 * Tracks frontend gallery visits and image views with country aggregation.
 */
final class FrontendTrackingService implements ServiceInterface
{
    private const OPTION_KEY = 'prox_gallery_tracking_stats';

    public function id(): string
    {
        return 'frontend.tracking';
    }

    public function boot(): void
    {
        /**
         * Fires after frontend tracking service boots.
         */
        \do_action('prox_gallery/service/frontend_tracking/booted', $this);
    }

    public function recordGalleryVisit(int $galleryId): void
    {
        if ($galleryId <= 0) {
            return;
        }

        $country = $this->resolveCountryCode();
        $dayKey = \gmdate('Y-m-d');
        $stats = $this->stats();
        $galleryKey = (string) $galleryId;

        if (! isset($stats['galleries'][$galleryKey]) || ! is_array($stats['galleries'][$galleryKey])) {
            $stats['galleries'][$galleryKey] = [
                'total' => 0,
                'countries' => [],
                'images' => [],
                'daily' => [],
            ];
        }

        if (! isset($stats['daily']['gallery_views']) || ! is_array($stats['daily']['gallery_views'])) {
            $stats['daily']['gallery_views'] = [];
        }

        $stats['galleries'][$galleryKey] = $this->incrementBucket($stats['galleries'][$galleryKey], $country, $dayKey);
        $stats['daily']['gallery_views'] = $this->incrementSeries($stats['daily']['gallery_views'], $dayKey);
        $stats['updated_at'] = \gmdate('c');
        \update_option(self::OPTION_KEY, $stats, false);
    }

    public function recordImageView(int $galleryId, int $imageId): void
    {
        if ($imageId <= 0) {
            return;
        }

        $country = $this->resolveCountryCode();
        $dayKey = \gmdate('Y-m-d');
        $stats = $this->stats();
        $imageKey = (string) $imageId;

        if (! isset($stats['images'][$imageKey]) || ! is_array($stats['images'][$imageKey])) {
            $stats['images'][$imageKey] = [
                'total' => 0,
                'countries' => [],
                'daily' => [],
            ];
        }

        if (! isset($stats['daily']['image_views']) || ! is_array($stats['daily']['image_views'])) {
            $stats['daily']['image_views'] = [];
        }

        $stats['images'][$imageKey] = $this->incrementBucket($stats['images'][$imageKey], $country, $dayKey);
        $stats['daily']['image_views'] = $this->incrementSeries($stats['daily']['image_views'], $dayKey);

        if ($galleryId > 0) {
            $galleryKey = (string) $galleryId;

            if (! isset($stats['galleries'][$galleryKey]) || ! is_array($stats['galleries'][$galleryKey])) {
                $stats['galleries'][$galleryKey] = [
                    'total' => 0,
                    'countries' => [],
                    'images' => [],
                    'daily' => [],
                ];
            }

            if (! isset($stats['galleries'][$galleryKey]['images']) || ! is_array($stats['galleries'][$galleryKey]['images'])) {
                $stats['galleries'][$galleryKey]['images'] = [];
            }

            if (! isset($stats['galleries'][$galleryKey]['images'][$imageKey]) || ! is_array($stats['galleries'][$galleryKey]['images'][$imageKey])) {
                $stats['galleries'][$galleryKey]['images'][$imageKey] = [
                    'total' => 0,
                    'countries' => [],
                    'daily' => [],
                ];
            }

            $stats['galleries'][$galleryKey]['images'][$imageKey] = $this->incrementBucket(
                $stats['galleries'][$galleryKey]['images'][$imageKey],
                $country,
                $dayKey
            );
        }

        $stats['updated_at'] = \gmdate('c');
        \update_option(self::OPTION_KEY, $stats, false);
    }

    /**
     * @return array{
     *   galleries:array<string, array{total:int, countries:array<string,int>, images:array<string, array{total:int, countries:array<string,int>, daily:array<string,int>}>, daily:array<string,int>}>,
     *   images:array<string, array{total:int, countries:array<string,int>, daily:array<string,int>}>,
     *   daily:array{gallery_views:array<string,int>, image_views:array<string,int>},
     *   updated_at:string
     * }
     */
    public function stats(): array
    {
        $value = \get_option(self::OPTION_KEY, []);

        if (! is_array($value)) {
            $value = [];
        }

        $galleries = isset($value['galleries']) && is_array($value['galleries']) ? $value['galleries'] : [];
        $images = isset($value['images']) && is_array($value['images']) ? $value['images'] : [];
        $daily = isset($value['daily']) && is_array($value['daily']) ? $value['daily'] : [];
        $updatedAt = isset($value['updated_at']) && is_string($value['updated_at']) ? $value['updated_at'] : '';

        return [
            'galleries' => $galleries,
            'images' => $images,
            'daily' => [
                'gallery_views' => isset($daily['gallery_views']) && is_array($daily['gallery_views']) ? $daily['gallery_views'] : [],
                'image_views' => isset($daily['image_views']) && is_array($daily['image_views']) ? $daily['image_views'] : [],
            ],
            'updated_at' => $updatedAt,
        ];
    }

    /**
     * @param array{total?:mixed,countries?:mixed,daily?:mixed} $bucket
     *
     * @return array{total:int,countries:array<string,int>,daily:array<string,int>}
     */
    private function incrementBucket(array $bucket, string $country, string $dayKey): array
    {
        $total = isset($bucket['total']) ? max(0, (int) $bucket['total']) : 0;
        $countries = isset($bucket['countries']) && is_array($bucket['countries']) ? $bucket['countries'] : [];
        $daily = isset($bucket['daily']) && is_array($bucket['daily']) ? $bucket['daily'] : [];

        $total += 1;
        $countries[$country] = isset($countries[$country]) ? ((int) $countries[$country]) + 1 : 1;
        $daily = $this->incrementSeries($daily, $dayKey);

        return [
            'total' => $total,
            'countries' => $countries,
            'daily' => $daily,
        ];
    }

    /**
     * @param array<string, int|numeric-string> $series
     *
     * @return array<string, int>
     */
    private function incrementSeries(array $series, string $dayKey): array
    {
        $normalized = [];

        foreach ($series as $key => $value) {
            $normalized[(string) $key] = max(0, (int) $value);
        }

        $normalized[$dayKey] = ($normalized[$dayKey] ?? 0) + 1;
        ksort($normalized);

        return $normalized;
    }

    private function resolveCountryCode(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null,
            $_SERVER['HTTP_X_COUNTRY_CODE'] ?? null,
            $_SERVER['HTTP_X_COUNTRY'] ?? null,
            $_SERVER['HTTP_X_GEO_COUNTRY'] ?? null,
            $_SERVER['HTTP_X_APPENGINE_COUNTRY'] ?? null,
            $_SERVER['HTTP_X_VERCEL_IP_COUNTRY'] ?? null,
            $_SERVER['HTTP_X_AZURE_CLIENTIPCOUNTRY'] ?? null,
            $_SERVER['GEOIP_COUNTRY_CODE'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $normalized = strtoupper(trim($candidate));

            if (preg_match('/^[A-Z]{2}$/', $normalized) === 1) {
                return $normalized;
            }
        }

        return 'ZZ';
    }
}
