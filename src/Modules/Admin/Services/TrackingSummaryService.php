<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Admin\Services;

use Prox\ProxGallery\Modules\Frontend\Services\FrontendTrackingService;
use Prox\ProxGallery\Modules\Gallery\Services\GalleryService;

/**
 * Builds admin tracking summary payloads from stored tracking statistics.
 */
final class TrackingSummaryService
{
    public function __construct(
        private FrontendTrackingService $tracking,
        private GalleryService $galleries
    ) {
    }

    /**
     * @return array{
     *   totals:array{gallery_views:int,image_views:int},
     *   countries:array<string,int>,
     *   galleries:list<array{gallery_id:int,name:string,total:int,countries:array<string,int>}>,
     *   images:list<array{image_id:int,title:string,total:int,countries:array<string,int>}>,
     *   updated_at:string
     * }
     */
    public function summary(): array
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
            'totals' => [
                'gallery_views' => $galleryViewsTotal,
                'image_views' => $imageViewsTotal,
            ],
            'countries' => $galleryCountries,
            'galleries' => $galleryItems,
            'images' => $imageItems,
            'updated_at' => isset($stats['updated_at']) ? (string) $stats['updated_at'] : '',
        ];
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
