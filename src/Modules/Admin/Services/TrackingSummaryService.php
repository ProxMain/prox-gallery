<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Admin\Services;

use Prox\ProxGallery\Modules\Frontend\Services\FrontendTrackingService;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryService;
use Prox\ProxGallery\Modules\MediaLibrary\DTO\TrackedImageDto;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaCategoryService;

/**
 * Builds admin tracking summary payloads from stored tracking statistics.
 */
final class TrackingSummaryService
{
    private const TREND_DAYS = 14;
    private const COMPARISON_DAYS = 7;

    public function __construct(
        private FrontendTrackingService $tracking,
        private GalleryService $galleries,
        private UploadedImageQueueModel $trackedImages
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $stats = $this->tracking->stats();
        $galleryRows = $this->galleries->list();
        $trackedImages = $this->trackedImages->all();
        $galleryMeta = [];
        $trackedImagesById = [];

        foreach ($galleryRows as $row) {
            $galleryId = (int) ($row['id'] ?? 0);

            if ($galleryId <= 0) {
                continue;
            }

            $galleryMeta[(string) $galleryId] = $row;
        }

        foreach ($trackedImages as $image) {
            $trackedImagesById[$image->id] = $image;
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
            $daily = isset($item['daily']) && is_array($item['daily']) ? $this->normalizeSeries($item['daily']) : [];
            $lightboxOpens = isset($item['lightbox_opens']) ? max(0, (int) $item['lightbox_opens']) : 0;
            $galleryViewsTotal += $total;

            foreach ($countries as $code => $count) {
                $key = strtoupper((string) $code);
                $galleryCountries[$key] = ($galleryCountries[$key] ?? 0) + max(0, (int) $count);
            }

            $galleryRow = $galleryMeta[(string) $galleryId] ?? null;
            $comparison = $this->comparisonFromSeries($daily);

            $galleryItems[] = [
                'gallery_id' => (int) $galleryId,
                'name' => is_array($galleryRow)
                    ? (string) ($galleryRow['name'] ?? ('Gallery #' . (int) $galleryId))
                    : ('Gallery #' . (int) $galleryId),
                'total' => $total,
                'countries' => $countries,
                'daily' => $daily,
                'template' => is_array($galleryRow) ? (string) ($galleryRow['template'] ?? 'basic-grid') : 'basic-grid',
                'image_count' => is_array($galleryRow) ? (int) ($galleryRow['image_count'] ?? 0) : 0,
                'created_at' => is_array($galleryRow) ? (string) ($galleryRow['created_at'] ?? '') : '',
                'has_description' => is_array($galleryRow) && trim((string) ($galleryRow['description'] ?? '')) !== '',
                'full_width_enabled' => is_array($galleryRow) && (bool) ($galleryRow['full_width_override'] ?? false),
                'lightbox_opens' => $lightboxOpens,
                'lightbox_rate' => $total > 0 ? round(($lightboxOpens / $total) * 100, 1) : 0.0,
                'current_period' => $comparison['current'],
                'previous_period' => $comparison['previous'],
                'delta' => $comparison['delta'],
                'delta_percentage' => $comparison['delta_percentage'],
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
            $daily = isset($item['daily']) && is_array($item['daily']) ? $this->normalizeSeries($item['daily']) : [];
            $lightboxOpens = isset($item['lightbox_opens']) ? max(0, (int) $item['lightbox_opens']) : 0;
            $infoOpens = isset($item['info_opens']) ? max(0, (int) $item['info_opens']) : 0;
            $imageViewsTotal += $total;
            $trackedImage = $trackedImagesById[$id] ?? null;
            $comparison = $this->comparisonFromSeries($daily);

            $imageItems[] = [
                'image_id' => $id,
                'title' => $this->imageTitle($id, $trackedImage),
                'total' => $total,
                'countries' => $countries,
                'daily' => $daily,
                'uploaded_at' => $trackedImage?->uploadedAt ?? '',
                'thumbnail_url' => $this->imageThumbnailUrl($id),
                'categories' => $this->categoriesForAttachment($id),
                'lightbox_opens' => $lightboxOpens,
                'lightbox_rate' => $total > 0 ? round(($lightboxOpens / $total) * 100, 1) : 0.0,
                'info_opens' => $infoOpens,
                'current_period' => $comparison['current'],
                'previous_period' => $comparison['previous'],
                'delta' => $comparison['delta'],
                'delta_percentage' => $comparison['delta_percentage'],
            ];
        }

        usort(
            $imageItems,
            static fn (array $left, array $right): int => ((int) $right['total']) <=> ((int) $left['total'])
        );

        $categoryItems = $this->categorySummary($trackedImages);
        $recentActivity = $this->recentActivity($galleryRows, $trackedImages);
        $portfolioGaps = $this->portfolioGaps($galleryRows, $trackedImages);
        $galleryTrend = $this->trendRows($stats['daily']['gallery_views'] ?? [], self::TREND_DAYS);
        $imageTrend = $this->trendRows($stats['daily']['image_views'] ?? [], self::TREND_DAYS);
        $galleryComparison = $this->comparisonFromSeries($stats['daily']['gallery_views'] ?? []);
        $imageComparison = $this->comparisonFromSeries($stats['daily']['image_views'] ?? []);
        $galleryItems = $this->withHealthScores($galleryItems);

        return [
            'totals' => [
                'gallery_views' => $galleryViewsTotal,
                'image_views' => $imageViewsTotal,
                'tracked_images' => count($trackedImages),
                'galleries' => count($galleryRows),
                'categories' => count($categoryItems),
            ],
            'countries' => $galleryCountries,
            'galleries' => $galleryItems,
            'images' => $imageItems,
            'categories' => $categoryItems,
            'trends' => [
                'gallery_views' => $galleryTrend,
                'image_views' => $imageTrend,
            ],
            'comparison' => [
                'gallery_views' => $galleryComparison,
                'image_views' => $imageComparison,
            ],
            'momentum' => [
                'galleries' => $this->momentumRows($galleryItems, 'gallery_id'),
                'images' => $this->momentumRows($imageItems, 'image_id'),
            ],
            'underperforming_galleries' => $this->underperformingGalleries($galleryItems),
            'fresh_uploads' => [
                'images' => $this->freshUploads($trackedImages, $imageItems),
                'galleries' => $this->freshGalleries($galleryRows, $galleryItems),
            ],
            'template_performance' => $this->templatePerformance($galleryItems),
            'layout_performance' => $this->layoutPerformance($galleryItems),
            'sources' => $this->aggregateRows($stats['sources'] ?? []),
            'devices' => $this->aggregateRows($stats['devices'] ?? []),
            'lightbox_engagement' => [
                'totals' => $this->lightboxTotals($stats, $galleryViewsTotal, $imageViewsTotal),
                'top_galleries' => $this->topLightboxGalleries($galleryItems),
                'top_images' => $this->topLightboxImages($imageItems),
            ],
            'seasonal' => [
                'gallery_views' => $this->monthlyRows($stats['daily']['gallery_views'] ?? []),
                'image_views' => $this->monthlyRows($stats['daily']['image_views'] ?? []),
            ],
            'recommendations' => $this->recommendations(
                $galleryItems,
                $imageItems,
                $portfolioGaps,
                $stats['sources'] ?? [],
                $stats['devices'] ?? []
            ),
            'recent_activity' => $recentActivity,
            'spotlight' => [
                'gallery' => $galleryItems[0] ?? null,
                'image' => $imageItems[0] ?? null,
            ],
            'portfolio_gaps' => $portfolioGaps,
            'updated_at' => isset($stats['updated_at']) ? (string) $stats['updated_at'] : '',
        ];
    }

    private function imageTitle(int $imageId, ?TrackedImageDto $trackedImage = null): string
    {
        if ($trackedImage instanceof TrackedImageDto && trim($trackedImage->title) !== '') {
            return $trackedImage->title;
        }

        $title = \get_the_title($imageId);

        if (! is_string($title) || trim($title) === '') {
            return '#' . $imageId;
        }

        return $title;
    }

    private function imageThumbnailUrl(int $imageId): string
    {
        $thumbnailUrl = \wp_get_attachment_image_url($imageId, 'medium');

        if (is_string($thumbnailUrl) && $thumbnailUrl !== '') {
            return $thumbnailUrl;
        }

        $url = \wp_get_attachment_url($imageId);

        return is_string($url) ? $url : '';
    }

    /**
     * @return list<array{id:int,name:string,slug:string}>
     */
    private function categoriesForAttachment(int $attachmentId): array
    {
        if (! \taxonomy_exists(MediaCategoryService::TAXONOMY)) {
            return [];
        }

        $terms = \wp_get_object_terms(
            $attachmentId,
            MediaCategoryService::TAXONOMY,
            [
                'orderby' => 'name',
                'order' => 'ASC',
            ]
        );

        if (! is_array($terms)) {
            return [];
        }

        $rows = [];

        foreach ($terms as $term) {
            if (! $term instanceof \WP_Term) {
                continue;
            }

            $rows[] = [
                'id' => (int) $term->term_id,
                'name' => (string) $term->name,
                'slug' => (string) $term->slug,
            ];
        }

        return $rows;
    }

    /**
     * @param list<TrackedImageDto> $trackedImages
     *
     * @return list<array{name:string,count:int}>
     */
    private function categorySummary(array $trackedImages): array
    {
        $counts = [];

        foreach ($trackedImages as $image) {
            foreach ($this->categoriesForAttachment($image->id) as $category) {
                $name = trim((string) ($category['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
        }

        arsort($counts);
        $rows = [];

        foreach ($counts as $name => $count) {
            $rows[] = [
                'name' => $name,
                'count' => (int) $count,
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $galleryRows
     * @param list<TrackedImageDto>       $trackedImages
     *
     * @return list<array{type:string,title:string,subtitle:string,timestamp:string}>
     */
    private function recentActivity(array $galleryRows, array $trackedImages): array
    {
        $items = [];

        foreach ($galleryRows as $gallery) {
            $timestamp = isset($gallery['created_at']) ? (string) $gallery['created_at'] : '';

            if ($timestamp === '') {
                continue;
            }

            $items[] = [
                'type' => 'gallery',
                'title' => (string) ($gallery['name'] ?? 'Untitled gallery'),
                'subtitle' => sprintf(
                    '%d image%s · %s',
                    (int) ($gallery['image_count'] ?? 0),
                    ((int) ($gallery['image_count'] ?? 0)) === 1 ? '' : 's',
                    (string) ($gallery['template'] ?? 'basic-grid')
                ),
                'timestamp' => $timestamp,
            ];
        }

        foreach ($trackedImages as $image) {
            if ($image->uploadedAt === '') {
                continue;
            }

            $items[] = [
                'type' => 'image',
                'title' => $image->title !== '' ? $image->title : ('#' . $image->id),
                'subtitle' => 'Tracked image' . ($image->uploadedBy !== '' ? ' · ' . $image->uploadedBy : ''),
                'timestamp' => $image->uploadedAt,
            ];
        }

        usort(
            $items,
            static fn (array $left, array $right): int => strtotime((string) $right['timestamp']) <=> strtotime((string) $left['timestamp'])
        );

        return array_slice($items, 0, 6);
    }

    /**
     * @param list<array<string, mixed>> $galleryRows
     * @param list<TrackedImageDto>       $trackedImages
     *
     * @return array<string, int>
     */
    private function portfolioGaps(array $galleryRows, array $trackedImages): array
    {
        $galleriesWithoutDescription = 0;
        $galleriesWithFewImages = 0;
        $emptyGalleries = 0;
        $uncategorizedImages = 0;

        foreach ($galleryRows as $gallery) {
            $description = trim((string) ($gallery['description'] ?? ''));
            $imageCount = (int) ($gallery['image_count'] ?? 0);

            if ($description === '') {
                $galleriesWithoutDescription++;
            }

            if ($imageCount === 0) {
                $emptyGalleries++;
            }

            if ($imageCount > 0 && $imageCount < 3) {
                $galleriesWithFewImages++;
            }
        }

        foreach ($trackedImages as $image) {
            if (count($this->categoriesForAttachment($image->id)) === 0) {
                $uncategorizedImages++;
            }
        }

        return [
            'galleries_without_description' => $galleriesWithoutDescription,
            'galleries_with_few_images' => $galleriesWithFewImages,
            'uncategorized_images' => $uncategorizedImages,
            'empty_galleries' => $emptyGalleries,
        ];
    }

    /**
     * @param array<string, int|numeric-string> $series
     *
     * @return array<string, int>
     */
    private function normalizeSeries(array $series): array
    {
        $normalized = [];

        foreach ($series as $day => $count) {
            $key = (string) $day;

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $key) !== 1) {
                continue;
            }

            $normalized[$key] = max(0, (int) $count);
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param array<string, int|numeric-string> $series
     *
     * @return list<array{date:string,label:string,count:int}>
     */
    private function trendRows(array $series, int $days): array
    {
        $normalized = $this->normalizeSeries($series);
        $rows = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = \gmdate('Y-m-d', strtotime('-' . $offset . ' days'));
            $rows[] = [
                'date' => $day,
                'label' => \gmdate('M j', strtotime($day . ' 00:00:00 UTC')),
                'count' => $normalized[$day] ?? 0,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, int|numeric-string> $series
     *
     * @return array{current:int,previous:int,delta:int,delta_percentage:int|null}
     */
    private function comparisonFromSeries(array $series, int $days = self::COMPARISON_DAYS): array
    {
        $normalized = $this->normalizeSeries($series);
        $current = 0;
        $previous = 0;

        for ($offset = 0; $offset < $days; $offset++) {
            $currentDay = \gmdate('Y-m-d', strtotime('-' . $offset . ' days'));
            $previousDay = \gmdate('Y-m-d', strtotime('-' . ($offset + $days) . ' days'));
            $current += $normalized[$currentDay] ?? 0;
            $previous += $normalized[$previousDay] ?? 0;
        }

        $delta = $current - $previous;
        $deltaPercentage = $previous > 0 ? (int) round(($delta / $previous) * 100) : null;

        return [
            'current' => $current,
            'previous' => $previous,
            'delta' => $delta,
            'delta_percentage' => $deltaPercentage,
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return list<array<string, mixed>>
     */
    private function withHealthScores(array $items): array
    {
        foreach ($items as $index => $item) {
            $score = 0;
            $score += min(40, (int) ($item['total'] ?? 0) * 4);
            $score += min(20, (int) ($item['current_period'] ?? 0) * 5);
            $score += ((int) ($item['image_count'] ?? 0) >= 6) ? 15 : (((int) ($item['image_count'] ?? 0) >= 3) ? 8 : 0);
            $score += ! empty($item['has_description']) ? 10 : 0;
            $score += ! empty($item['full_width_enabled']) ? 5 : 0;

            if ((int) ($item['delta'] ?? 0) > 0) {
                $score += 10;
            } elseif ((int) ($item['delta'] ?? 0) < 0) {
                $score -= 8;
            }

            $items[$index]['health_score'] = max(0, min(100, $score));
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return list<array<string, mixed>>
     */
    private function momentumRows(array $items, string $idKey): array
    {
        $rows = array_values(
            array_filter(
                $items,
                static fn (array $item): bool => (int) ($item['delta'] ?? 0) > 0
            )
        );

        usort(
            $rows,
            static fn (array $left, array $right): int => ((int) $right['delta']) <=> ((int) $left['delta'])
        );

        return array_map(
            static fn (array $item): array => [
                'id' => (int) ($item[$idKey] ?? 0),
                'name' => (string) ($item['name'] ?? $item['title'] ?? ''),
                'delta' => (int) ($item['delta'] ?? 0),
                'current_period' => (int) ($item['current_period'] ?? 0),
                'previous_period' => (int) ($item['previous_period'] ?? 0),
            ],
            array_slice($rows, 0, 4)
        );
    }

    /**
     * @param list<array<string, mixed>> $galleryItems
     *
     * @return list<array<string, mixed>>
     */
    private function underperformingGalleries(array $galleryItems): array
    {
        $rows = array_values(
            array_filter(
                $galleryItems,
                static fn (array $item): bool => (int) ($item['total'] ?? 0) > 0
            )
        );

        usort(
            $rows,
            static function (array $left, array $right): int {
                $leftScore = ((int) ($left['delta'] ?? 0) * 1000) + (int) ($left['current_period'] ?? 0);
                $rightScore = ((int) ($right['delta'] ?? 0) * 1000) + (int) ($right['current_period'] ?? 0);

                return $leftScore <=> $rightScore;
            }
        );

        return array_slice($rows, 0, 4);
    }

    /**
     * @param list<TrackedImageDto>       $trackedImages
     * @param list<array<string, mixed>>  $imageItems
     *
     * @return list<array<string, mixed>>
     */
    private function freshUploads(array $trackedImages, array $imageItems): array
    {
        $imageStatsById = [];

        foreach ($imageItems as $item) {
            $imageStatsById[(int) ($item['image_id'] ?? 0)] = $item;
        }

        usort(
            $trackedImages,
            static fn (TrackedImageDto $left, TrackedImageDto $right): int => strtotime($right->uploadedAt) <=> strtotime($left->uploadedAt)
        );

        $rows = [];

        foreach (array_slice($trackedImages, 0, 4) as $image) {
            $stats = $imageStatsById[$image->id] ?? null;

            $rows[] = [
                'image_id' => $image->id,
                'title' => $image->title !== '' ? $image->title : ('#' . $image->id),
                'uploaded_at' => $image->uploadedAt,
                'thumbnail_url' => $this->imageThumbnailUrl($image->id),
                'total' => is_array($stats) ? (int) ($stats['total'] ?? 0) : 0,
                'current_period' => is_array($stats) ? (int) ($stats['current_period'] ?? 0) : 0,
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $galleryRows
     * @param list<array<string, mixed>> $galleryItems
     *
     * @return list<array<string, mixed>>
     */
    private function freshGalleries(array $galleryRows, array $galleryItems): array
    {
        $statsById = [];

        foreach ($galleryItems as $item) {
            $statsById[(int) ($item['gallery_id'] ?? 0)] = $item;
        }

        usort(
            $galleryRows,
            static fn (array $left, array $right): int => strtotime((string) ($right['created_at'] ?? '')) <=> strtotime((string) ($left['created_at'] ?? ''))
        );

        $rows = [];

        foreach (array_slice($galleryRows, 0, 4) as $gallery) {
            $galleryId = (int) ($gallery['id'] ?? 0);
            $stats = $statsById[$galleryId] ?? null;

            $rows[] = [
                'gallery_id' => $galleryId,
                'name' => (string) ($gallery['name'] ?? 'Untitled gallery'),
                'created_at' => (string) ($gallery['created_at'] ?? ''),
                'template' => (string) ($gallery['template'] ?? 'basic-grid'),
                'image_count' => (int) ($gallery['image_count'] ?? 0),
                'total' => is_array($stats) ? (int) ($stats['total'] ?? 0) : 0,
                'current_period' => is_array($stats) ? (int) ($stats['current_period'] ?? 0) : 0,
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $galleryItems
     *
     * @return list<array<string, mixed>>
     */
    private function templatePerformance(array $galleryItems): array
    {
        $rows = [];

        foreach ($galleryItems as $item) {
            $template = (string) ($item['template'] ?? 'basic-grid');

            if (! isset($rows[$template])) {
                $rows[$template] = [
                    'template' => $template,
                    'galleries' => 0,
                    'visits' => 0,
                    'current_period' => 0,
                    'previous_period' => 0,
                ];
            }

            $rows[$template]['galleries']++;
            $rows[$template]['visits'] += (int) ($item['total'] ?? 0);
            $rows[$template]['current_period'] += (int) ($item['current_period'] ?? 0);
            $rows[$template]['previous_period'] += (int) ($item['previous_period'] ?? 0);
        }

        foreach ($rows as &$row) {
            $row['avg_visits'] = $row['galleries'] > 0 ? (int) round($row['visits'] / $row['galleries']) : 0;
            $row['delta'] = $row['current_period'] - $row['previous_period'];
        }
        unset($row);

        usort(
            $rows,
            static fn (array $left, array $right): int => ((int) $right['visits']) <=> ((int) $left['visits'])
        );

        return array_values($rows);
    }

    /**
     * @param list<array<string, mixed>> $galleryItems
     *
     * @return list<array<string, mixed>>
     */
    private function layoutPerformance(array $galleryItems): array
    {
        $rows = [
            'full-width' => ['label' => 'Full width', 'galleries' => 0, 'visits' => 0, 'current_period' => 0, 'previous_period' => 0],
            'contained' => ['label' => 'Contained', 'galleries' => 0, 'visits' => 0, 'current_period' => 0, 'previous_period' => 0],
        ];

        foreach ($galleryItems as $item) {
            $key = ! empty($item['full_width_enabled']) ? 'full-width' : 'contained';
            $rows[$key]['galleries']++;
            $rows[$key]['visits'] += (int) ($item['total'] ?? 0);
            $rows[$key]['current_period'] += (int) ($item['current_period'] ?? 0);
            $rows[$key]['previous_period'] += (int) ($item['previous_period'] ?? 0);
        }

        foreach ($rows as &$row) {
            $row['avg_visits'] = $row['galleries'] > 0 ? (int) round($row['visits'] / $row['galleries']) : 0;
            $row['delta'] = $row['current_period'] - $row['previous_period'];
        }
        unset($row);

        return array_values($rows);
    }

    /**
     * @param array<string, int|numeric-string> $map
     *
     * @return list<array{label:string,count:int}>
     */
    private function aggregateRows(array $map): array
    {
        $rows = [];

        foreach ($map as $label => $count) {
            $rows[] = [
                'label' => (string) $label,
                'count' => max(0, (int) $count),
            ];
        }

        usort(
            $rows,
            static fn (array $left, array $right): int => ((int) $right['count']) <=> ((int) $left['count'])
        );

        return $rows;
    }

    /**
     * @param array<string, mixed> $stats
     *
     * @return array<string, float|int>
     */
    private function lightboxTotals(array $stats, int $galleryViewsTotal, int $imageViewsTotal): array
    {
        $lightboxSeries = isset($stats['daily']['lightbox_opens']) && is_array($stats['daily']['lightbox_opens'])
            ? $this->normalizeSeries($stats['daily']['lightbox_opens'])
            : [];
        $infoSeries = isset($stats['daily']['info_panel_opens']) && is_array($stats['daily']['info_panel_opens'])
            ? $this->normalizeSeries($stats['daily']['info_panel_opens'])
            : [];

        $lightboxOpens = array_sum($lightboxSeries);
        $infoOpens = array_sum($infoSeries);

        return [
            'lightbox_opens' => $lightboxOpens,
            'info_panel_opens' => $infoOpens,
            'lightbox_rate_per_gallery_visit' => $galleryViewsTotal > 0 ? round(($lightboxOpens / $galleryViewsTotal) * 100, 1) : 0.0,
            'info_rate_per_image_view' => $imageViewsTotal > 0 ? round(($infoOpens / $imageViewsTotal) * 100, 1) : 0.0,
        ];
    }

    /**
     * @param list<array<string, mixed>> $galleryItems
     *
     * @return list<array<string, mixed>>
     */
    private function topLightboxGalleries(array $galleryItems): array
    {
        $rows = array_values(
            array_filter(
                $galleryItems,
                static fn (array $item): bool => (int) ($item['lightbox_opens'] ?? 0) > 0
            )
        );

        usort(
            $rows,
            static fn (array $left, array $right): int => ((int) $right['lightbox_opens']) <=> ((int) $left['lightbox_opens'])
        );

        return array_slice($rows, 0, 4);
    }

    /**
     * @param list<array<string, mixed>> $imageItems
     *
     * @return list<array<string, mixed>>
     */
    private function topLightboxImages(array $imageItems): array
    {
        $rows = array_values(
            array_filter(
                $imageItems,
                static fn (array $item): bool => (int) ($item['lightbox_opens'] ?? 0) > 0
            )
        );

        usort(
            $rows,
            static fn (array $left, array $right): int => ((int) $right['lightbox_opens']) <=> ((int) $left['lightbox_opens'])
        );

        return array_slice($rows, 0, 4);
    }

    /**
     * @param array<string, int|numeric-string> $series
     *
     * @return list<array{label:string,count:int}>
     */
    private function monthlyRows(array $series): array
    {
        $normalized = $this->normalizeSeries($series);
        $months = [];

        foreach ($normalized as $day => $count) {
            $monthKey = substr($day, 0, 7);
            $months[$monthKey] = ($months[$monthKey] ?? 0) + $count;
        }

        ksort($months);
        $rows = [];

        foreach (array_slice($months, -12, 12, true) as $monthKey => $count) {
            $rows[] = [
                'label' => \gmdate('M Y', strtotime($monthKey . '-01 00:00:00 UTC')),
                'count' => (int) $count,
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $galleryItems
     * @param list<array<string, mixed>> $imageItems
     * @param array<string, int>         $portfolioGaps
     * @param array<string, int|numeric-string> $sources
     * @param array<string, int|numeric-string> $devices
     *
     * @return list<array{title:string,detail:string,tone:string}>
     */
    private function recommendations(
        array $galleryItems,
        array $imageItems,
        array $portfolioGaps,
        array $sources,
        array $devices
    ): array {
        $recommendations = [];
        $topMomentumGallery = $this->momentumRows($galleryItems, 'gallery_id')[0] ?? null;
        $underperformingGallery = $this->underperformingGalleries($galleryItems)[0] ?? null;
        $topSource = $this->aggregateRows($sources)[0] ?? null;
        $topDevice = $this->aggregateRows($devices)[0] ?? null;
        $topLightboxImage = $this->topLightboxImages($imageItems)[0] ?? null;

        if (is_array($topMomentumGallery)) {
            $recommendations[] = [
                'title' => 'Promote the gallery gaining momentum',
                'detail' => sprintf('%s is up by %d visits in the current period.', (string) $topMomentumGallery['name'], (int) $topMomentumGallery['delta']),
                'tone' => 'positive',
            ];
        }

        if (is_array($underperformingGallery)) {
            $recommendations[] = [
                'title' => 'Refresh a slipping gallery',
                'detail' => sprintf('%s is lagging in the current window. Consider updating images, copy, or placement.', (string) $underperformingGallery['name']),
                'tone' => 'warning',
            ];
        }

        if (($portfolioGaps['galleries_without_description'] ?? 0) > 0) {
            $recommendations[] = [
                'title' => 'Fill missing gallery descriptions',
                'detail' => sprintf('%d galleries still have no description, which weakens editorial presentation.', (int) $portfolioGaps['galleries_without_description']),
                'tone' => 'warning',
            ];
        }

        if (($portfolioGaps['uncategorized_images'] ?? 0) > 0) {
            $recommendations[] = [
                'title' => 'Categorize more tracked images',
                'detail' => sprintf('%d tracked images are uncategorized, making portfolio discovery weaker.', (int) $portfolioGaps['uncategorized_images']),
                'tone' => 'warning',
            ];
        }

        if (is_array($topSource)) {
            $recommendations[] = [
                'title' => 'Lean into your strongest traffic source',
                'detail' => sprintf('%s is currently driving the most tracked visits.', (string) $topSource['label']),
                'tone' => 'positive',
            ];
        }

        if (is_array($topDevice)) {
            $recommendations[] = [
                'title' => 'Prioritize your dominant device experience',
                'detail' => sprintf('%s visitors currently make up the biggest share of interaction traffic.', (string) $topDevice['label']),
                'tone' => 'neutral',
            ];
        }

        if (is_array($topLightboxImage)) {
            $recommendations[] = [
                'title' => 'Feature images with strong lightbox intent',
                'detail' => sprintf('%s is attracting the most lightbox opens, suggesting strong curiosity or detail interest.', (string) $topLightboxImage['title']),
                'tone' => 'positive',
            ];
        }

        return array_slice($recommendations, 0, 6);
    }
}
