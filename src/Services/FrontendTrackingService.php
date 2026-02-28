<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Services;

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
        $stats = $this->stats();
        $galleryKey = (string) $galleryId;

        if (! isset($stats['galleries'][$galleryKey]) || ! is_array($stats['galleries'][$galleryKey])) {
            $stats['galleries'][$galleryKey] = [
                'total' => 0,
                'countries' => [],
                'images' => [],
            ];
        }

        $stats['galleries'][$galleryKey] = $this->incrementBucket($stats['galleries'][$galleryKey], $country);
        $stats['updated_at'] = \gmdate('c');
        \update_option(self::OPTION_KEY, $stats, false);
    }

    public function recordImageView(int $galleryId, int $imageId): void
    {
        if ($imageId <= 0) {
            return;
        }

        $country = $this->resolveCountryCode();
        $stats = $this->stats();
        $imageKey = (string) $imageId;

        if (! isset($stats['images'][$imageKey]) || ! is_array($stats['images'][$imageKey])) {
            $stats['images'][$imageKey] = [
                'total' => 0,
                'countries' => [],
            ];
        }

        $stats['images'][$imageKey] = $this->incrementBucket($stats['images'][$imageKey], $country);

        if ($galleryId > 0) {
            $galleryKey = (string) $galleryId;

            if (! isset($stats['galleries'][$galleryKey]) || ! is_array($stats['galleries'][$galleryKey])) {
                $stats['galleries'][$galleryKey] = [
                    'total' => 0,
                    'countries' => [],
                    'images' => [],
                ];
            }

            if (! isset($stats['galleries'][$galleryKey]['images']) || ! is_array($stats['galleries'][$galleryKey]['images'])) {
                $stats['galleries'][$galleryKey]['images'] = [];
            }

            if (! isset($stats['galleries'][$galleryKey]['images'][$imageKey]) || ! is_array($stats['galleries'][$galleryKey]['images'][$imageKey])) {
                $stats['galleries'][$galleryKey]['images'][$imageKey] = [
                    'total' => 0,
                    'countries' => [],
                ];
            }

            $stats['galleries'][$galleryKey]['images'][$imageKey] = $this->incrementBucket(
                $stats['galleries'][$galleryKey]['images'][$imageKey],
                $country
            );
        }

        $stats['updated_at'] = \gmdate('c');
        \update_option(self::OPTION_KEY, $stats, false);
    }

    /**
     * @return array{
     *   galleries:array<string, array{total:int, countries:array<string,int>, images:array<string, array{total:int, countries:array<string,int>}>}>,
     *   images:array<string, array{total:int, countries:array<string,int>}>,
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
        $updatedAt = isset($value['updated_at']) && is_string($value['updated_at']) ? $value['updated_at'] : '';

        return [
            'galleries' => $galleries,
            'images' => $images,
            'updated_at' => $updatedAt,
        ];
    }

    /**
     * @param array{total?:mixed,countries?:mixed} $bucket
     *
     * @return array{total:int,countries:array<string,int>}
     */
    private function incrementBucket(array $bucket, string $country): array
    {
        $total = isset($bucket['total']) ? max(0, (int) $bucket['total']) : 0;
        $countries = isset($bucket['countries']) && is_array($bucket['countries']) ? $bucket['countries'] : [];

        $total += 1;
        $countries[$country] = isset($countries[$country]) ? ((int) $countries[$country]) + 1 : 1;

        return [
            'total' => $total,
            'countries' => $countries,
        ];
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

