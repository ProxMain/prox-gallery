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
        $this->recordGalleryVisitWithContext($galleryId);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function recordGalleryVisitWithContext(int $galleryId, array $context = []): void
    {
        if ($galleryId <= 0) {
            return;
        }

        $country = $this->resolveCountryCode();
        $dayKey = \gmdate('Y-m-d');
        $stats = $this->stats();
        $galleryKey = (string) $galleryId;
        $source = $this->resolveSource($context);
        $device = $this->resolveDeviceType($context);

        if (! isset($stats['galleries'][$galleryKey]) || ! is_array($stats['galleries'][$galleryKey])) {
            $stats['galleries'][$galleryKey] = [
                'total' => 0,
                'countries' => [],
                'images' => [],
                'daily' => [],
                'lightbox_opens' => 0,
                'lightbox_daily' => [],
            ];
        }

        if (! isset($stats['daily']['gallery_views']) || ! is_array($stats['daily']['gallery_views'])) {
            $stats['daily']['gallery_views'] = [];
        }

        $stats['galleries'][$galleryKey] = $this->incrementBucket($stats['galleries'][$galleryKey], $country, $dayKey);
        $stats['daily']['gallery_views'] = $this->incrementSeries($stats['daily']['gallery_views'], $dayKey);
        $stats['sources'] = $this->incrementAggregateMap($stats['sources'] ?? [], $source);
        $stats['devices'] = $this->incrementAggregateMap($stats['devices'] ?? [], $device);
        $stats['updated_at'] = \gmdate('c');
        \update_option(self::OPTION_KEY, $stats, false);
    }

    public function recordImageView(int $galleryId, int $imageId): void
    {
        $this->recordImageViewWithContext($galleryId, $imageId);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function recordImageViewWithContext(int $galleryId, int $imageId, array $context = []): void
    {
        if ($imageId <= 0) {
            return;
        }

        $country = $this->resolveCountryCode();
        $dayKey = \gmdate('Y-m-d');
        $stats = $this->stats();
        $imageKey = (string) $imageId;
        $device = $this->resolveDeviceType($context);

        if (! isset($stats['images'][$imageKey]) || ! is_array($stats['images'][$imageKey])) {
            $stats['images'][$imageKey] = [
                'total' => 0,
                'countries' => [],
                'daily' => [],
                'lightbox_opens' => 0,
                'lightbox_daily' => [],
                'info_opens' => 0,
                'info_daily' => [],
            ];
        }

        if (! isset($stats['daily']['image_views']) || ! is_array($stats['daily']['image_views'])) {
            $stats['daily']['image_views'] = [];
        }

        $stats['images'][$imageKey] = $this->incrementBucket($stats['images'][$imageKey], $country, $dayKey);
        $stats['daily']['image_views'] = $this->incrementSeries($stats['daily']['image_views'], $dayKey);
        $stats['devices'] = $this->incrementAggregateMap($stats['devices'] ?? [], $device);

        if ($galleryId > 0) {
            $galleryKey = (string) $galleryId;

            if (! isset($stats['galleries'][$galleryKey]) || ! is_array($stats['galleries'][$galleryKey])) {
                $stats['galleries'][$galleryKey] = [
                    'total' => 0,
                    'countries' => [],
                    'images' => [],
                    'daily' => [],
                    'lightbox_opens' => 0,
                    'lightbox_daily' => [],
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
                    'lightbox_opens' => 0,
                    'lightbox_daily' => [],
                    'info_opens' => 0,
                    'info_daily' => [],
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
     * @param array<string, mixed> $context
     */
    public function recordLightboxOpen(int $galleryId, int $imageId, array $context = []): void
    {
        if ($imageId <= 0) {
            return;
        }

        $dayKey = \gmdate('Y-m-d');
        $stats = $this->stats();
        $imageKey = (string) $imageId;
        $device = $this->resolveDeviceType($context);

        if (! isset($stats['daily']['lightbox_opens']) || ! is_array($stats['daily']['lightbox_opens'])) {
            $stats['daily']['lightbox_opens'] = [];
        }

        if (! isset($stats['images'][$imageKey]) || ! is_array($stats['images'][$imageKey])) {
            $stats['images'][$imageKey] = [
                'total' => 0,
                'countries' => [],
                'daily' => [],
                'lightbox_opens' => 0,
                'lightbox_daily' => [],
                'info_opens' => 0,
                'info_daily' => [],
            ];
        }

        $stats['images'][$imageKey]['lightbox_opens'] = (int) ($stats['images'][$imageKey]['lightbox_opens'] ?? 0) + 1;
        $stats['images'][$imageKey]['lightbox_daily'] = $this->incrementSeries($stats['images'][$imageKey]['lightbox_daily'] ?? [], $dayKey);
        $stats['daily']['lightbox_opens'] = $this->incrementSeries($stats['daily']['lightbox_opens'], $dayKey);
        $stats['devices'] = $this->incrementAggregateMap($stats['devices'] ?? [], $device);

        if ($galleryId > 0) {
            $galleryKey = (string) $galleryId;

            if (! isset($stats['galleries'][$galleryKey]) || ! is_array($stats['galleries'][$galleryKey])) {
                $stats['galleries'][$galleryKey] = [
                    'total' => 0,
                    'countries' => [],
                    'images' => [],
                    'daily' => [],
                    'lightbox_opens' => 0,
                    'lightbox_daily' => [],
                ];
            }

            $stats['galleries'][$galleryKey]['lightbox_opens'] = (int) ($stats['galleries'][$galleryKey]['lightbox_opens'] ?? 0) + 1;
            $stats['galleries'][$galleryKey]['lightbox_daily'] = $this->incrementSeries($stats['galleries'][$galleryKey]['lightbox_daily'] ?? [], $dayKey);

            if (! isset($stats['galleries'][$galleryKey]['images'][$imageKey]) || ! is_array($stats['galleries'][$galleryKey]['images'][$imageKey])) {
                $stats['galleries'][$galleryKey]['images'][$imageKey] = [
                    'total' => 0,
                    'countries' => [],
                    'daily' => [],
                    'lightbox_opens' => 0,
                    'lightbox_daily' => [],
                    'info_opens' => 0,
                    'info_daily' => [],
                ];
            }

            $stats['galleries'][$galleryKey]['images'][$imageKey]['lightbox_opens'] =
                (int) ($stats['galleries'][$galleryKey]['images'][$imageKey]['lightbox_opens'] ?? 0) + 1;
            $stats['galleries'][$galleryKey]['images'][$imageKey]['lightbox_daily'] =
                $this->incrementSeries($stats['galleries'][$galleryKey]['images'][$imageKey]['lightbox_daily'] ?? [], $dayKey);
        }

        $stats['updated_at'] = \gmdate('c');
        \update_option(self::OPTION_KEY, $stats, false);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function recordInfoPanelOpen(int $galleryId, int $imageId, array $context = []): void
    {
        if ($imageId <= 0) {
            return;
        }

        $dayKey = \gmdate('Y-m-d');
        $stats = $this->stats();
        $imageKey = (string) $imageId;
        $device = $this->resolveDeviceType($context);

        if (! isset($stats['daily']['info_panel_opens']) || ! is_array($stats['daily']['info_panel_opens'])) {
            $stats['daily']['info_panel_opens'] = [];
        }

        if (! isset($stats['images'][$imageKey]) || ! is_array($stats['images'][$imageKey])) {
            $stats['images'][$imageKey] = [
                'total' => 0,
                'countries' => [],
                'daily' => [],
                'lightbox_opens' => 0,
                'lightbox_daily' => [],
                'info_opens' => 0,
                'info_daily' => [],
            ];
        }

        $stats['images'][$imageKey]['info_opens'] = (int) ($stats['images'][$imageKey]['info_opens'] ?? 0) + 1;
        $stats['images'][$imageKey]['info_daily'] = $this->incrementSeries($stats['images'][$imageKey]['info_daily'] ?? [], $dayKey);
        $stats['daily']['info_panel_opens'] = $this->incrementSeries($stats['daily']['info_panel_opens'], $dayKey);
        $stats['devices'] = $this->incrementAggregateMap($stats['devices'] ?? [], $device);

        if ($galleryId > 0) {
            $galleryKey = (string) $galleryId;

            if (! isset($stats['galleries'][$galleryKey]['images'][$imageKey]) || ! is_array($stats['galleries'][$galleryKey]['images'][$imageKey])) {
                $stats['galleries'][$galleryKey]['images'][$imageKey] = [
                    'total' => 0,
                    'countries' => [],
                    'daily' => [],
                    'lightbox_opens' => 0,
                    'lightbox_daily' => [],
                    'info_opens' => 0,
                    'info_daily' => [],
                ];
            }

            $stats['galleries'][$galleryKey]['images'][$imageKey]['info_opens'] =
                (int) ($stats['galleries'][$galleryKey]['images'][$imageKey]['info_opens'] ?? 0) + 1;
            $stats['galleries'][$galleryKey]['images'][$imageKey]['info_daily'] =
                $this->incrementSeries($stats['galleries'][$galleryKey]['images'][$imageKey]['info_daily'] ?? [], $dayKey);
        }

        $stats['updated_at'] = \gmdate('c');
        \update_option(self::OPTION_KEY, $stats, false);
    }

    /**
     * @return array{
     *   galleries:array<string, array{total:int, countries:array<string,int>, images:array<string, array{total:int, countries:array<string,int>, daily:array<string,int>}>, daily:array<string,int>}>,
     *   images:array<string, array{total:int, countries:array<string,int>, daily:array<string,int>}>,
     *   daily:array{
     *     gallery_views:array<string,int>,
     *     image_views:array<string,int>,
     *     lightbox_opens:array<string,int>,
     *     info_panel_opens:array<string,int>
     *   },
     *   sources:array<string,int>,
     *   devices:array<string,int>,
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
        $sources = isset($value['sources']) && is_array($value['sources']) ? $value['sources'] : [];
        $devices = isset($value['devices']) && is_array($value['devices']) ? $value['devices'] : [];
        $updatedAt = isset($value['updated_at']) && is_string($value['updated_at']) ? $value['updated_at'] : '';

        return [
            'galleries' => $galleries,
            'images' => $images,
            'daily' => [
                'gallery_views' => isset($daily['gallery_views']) && is_array($daily['gallery_views']) ? $daily['gallery_views'] : [],
                'image_views' => isset($daily['image_views']) && is_array($daily['image_views']) ? $daily['image_views'] : [],
                'lightbox_opens' => isset($daily['lightbox_opens']) && is_array($daily['lightbox_opens']) ? $daily['lightbox_opens'] : [],
                'info_panel_opens' => isset($daily['info_panel_opens']) && is_array($daily['info_panel_opens']) ? $daily['info_panel_opens'] : [],
            ],
            'sources' => $sources,
            'devices' => $devices,
            'updated_at' => $updatedAt,
        ];
    }

    /**
     * @param array<string, mixed> $stats
     */
    public function replaceStats(array $stats): void
    {
        \update_option(
            self::OPTION_KEY,
            [
                'galleries' => isset($stats['galleries']) && is_array($stats['galleries']) ? $stats['galleries'] : [],
                'images' => isset($stats['images']) && is_array($stats['images']) ? $stats['images'] : [],
                'daily' => isset($stats['daily']) && is_array($stats['daily']) ? $stats['daily'] : [
                    'gallery_views' => [],
                    'image_views' => [],
                    'lightbox_opens' => [],
                    'info_panel_opens' => [],
                ],
                'sources' => isset($stats['sources']) && is_array($stats['sources']) ? $stats['sources'] : [],
                'devices' => isset($stats['devices']) && is_array($stats['devices']) ? $stats['devices'] : [],
                'updated_at' => isset($stats['updated_at']) && is_string($stats['updated_at'])
                    ? $stats['updated_at']
                    : \gmdate('c'),
            ],
            false
        );
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

    /**
     * @param array<string, int|numeric-string> $map
     *
     * @return array<string, int>
     */
    private function incrementAggregateMap(array $map, string $key): array
    {
        $normalized = [];

        foreach ($map as $mapKey => $value) {
            $normalized[(string) $mapKey] = max(0, (int) $value);
        }

        $resolvedKey = trim($key) !== '' ? trim($key) : 'unknown';
        $normalized[$resolvedKey] = ($normalized[$resolvedKey] ?? 0) + 1;
        arsort($normalized);

        return $normalized;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveSource(array $context): string
    {
        $rawReferrer = isset($context['referrer']) ? trim((string) $context['referrer']) : '';
        $currentUrl = isset($context['current_url']) ? trim((string) $context['current_url']) : '';

        if ($rawReferrer === '') {
            return 'direct';
        }

        $referrerHost = (string) (\wp_parse_url($rawReferrer, PHP_URL_HOST) ?? '');
        $currentHost = (string) (\wp_parse_url($currentUrl, PHP_URL_HOST) ?? '');

        if ($referrerHost === '') {
            return 'direct';
        }

        if ($currentHost !== '' && $referrerHost === $currentHost) {
            return 'internal';
        }

        $normalized = strtolower(preg_replace('/^www\./', '', $referrerHost));

        if (str_contains($normalized, 'instagram')) {
            return 'instagram';
        }

        if (str_contains($normalized, 'facebook')) {
            return 'facebook';
        }

        if (str_contains($normalized, 'google')) {
            return 'google';
        }

        if (str_contains($normalized, 'pinterest')) {
            return 'pinterest';
        }

        if (str_contains($normalized, 'linkedin')) {
            return 'linkedin';
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveDeviceType(array $context): string
    {
        $provided = strtolower(trim((string) ($context['device_type'] ?? '')));

        if (in_array($provided, ['mobile', 'tablet', 'desktop'], true)) {
            return $provided;
        }

        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower((string) $_SERVER['HTTP_USER_AGENT']) : '';

        if ($userAgent !== '') {
            if (str_contains($userAgent, 'ipad') || str_contains($userAgent, 'tablet')) {
                return 'tablet';
            }

            if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'iphone') || str_contains($userAgent, 'android')) {
                return 'mobile';
            }
        }

        return 'desktop';
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
