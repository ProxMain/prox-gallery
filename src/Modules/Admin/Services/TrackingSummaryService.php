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
    public function __construct(
        private FrontendTrackingService $tracking,
        private GalleryService $galleries,
        private UploadedImageQueueModel $trackedImages
    ) {
    }

    /**
     * @return array{
     *   totals:array{gallery_views:int,image_views:int,tracked_images:int,galleries:int,categories:int},
     *   countries:array<string,int>,
     *   galleries:list<array<string,mixed>>,
     *   images:list<array<string,mixed>>,
     *   categories:list<array<string,mixed>>,
     *   recent_activity:list<array<string,mixed>>,
     *   spotlight:array<string,mixed>,
     *   portfolio_gaps:array<string,mixed>,
     *   updated_at:string
     * }
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
            $galleryViewsTotal += $total;

            foreach ($countries as $code => $count) {
                $key = strtoupper((string) $code);
                $galleryCountries[$key] = ($galleryCountries[$key] ?? 0) + max(0, (int) $count);
            }

            $galleryRow = $galleryMeta[(string) $galleryId] ?? null;

            $galleryItems[] = [
                'gallery_id' => (int) $galleryId,
                'name' => is_array($galleryRow)
                    ? (string) ($galleryRow['name'] ?? ('Gallery #' . (int) $galleryId))
                    : ('Gallery #' . (int) $galleryId),
                'total' => $total,
                'countries' => $countries,
                'template' => is_array($galleryRow) ? (string) ($galleryRow['template'] ?? 'basic-grid') : 'basic-grid',
                'image_count' => is_array($galleryRow) ? (int) ($galleryRow['image_count'] ?? 0) : 0,
                'created_at' => is_array($galleryRow) ? (string) ($galleryRow['created_at'] ?? '') : '',
                'has_description' => is_array($galleryRow) && trim((string) ($galleryRow['description'] ?? '')) !== '',
                'full_width_enabled' => is_array($galleryRow) && (bool) ($galleryRow['full_width_override'] ?? false),
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
            $trackedImage = $trackedImagesById[$id] ?? null;

            $imageItems[] = [
                'image_id' => $id,
                'title' => $this->imageTitle($id, $trackedImage),
                'total' => $total,
                'countries' => $countries,
                'uploaded_at' => $trackedImage?->uploadedAt ?? '',
                'thumbnail_url' => $this->imageThumbnailUrl($id),
                'categories' => $this->categoriesForAttachment($id),
            ];
        }

        usort(
            $imageItems,
            static fn (array $left, array $right): int => ((int) $right['total']) <=> ((int) $left['total'])
        );

        $categoryItems = $this->categorySummary($trackedImages);
        $recentActivity = $this->recentActivity($galleryRows, $trackedImages);
        $portfolioGaps = $this->portfolioGaps($galleryRows, $trackedImages);

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
     * @return array{
     *   galleries_without_description:int,
     *   galleries_with_few_images:int,
     *   uncategorized_images:int,
     *   empty_galleries:int
     * }
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
}
