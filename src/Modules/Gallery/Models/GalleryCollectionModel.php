<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\Gallery\Models;

use Prox\ProxGallery\Contracts\ModelInterface;

/**
 * Stores gallery rows for the admin gallery module.
 */
final class GalleryCollectionModel implements ModelInterface
{
    public function id(): string
    {
        return 'gallery.collection';
    }

    public function optionKey(): string
    {
        return 'prox_gallery_galleries';
    }

    /**
     * @return list<array{
     *   id:int,
     *   name:string,
     *   description:string,
     *   template:string,
     *   grid_columns_override:int|null,
     *   lightbox_override:bool|null,
     *   hover_zoom_override:bool|null,
     *   full_width_override:bool|null,
     *   transition_override:string|null,
     *   created_at:string,
     *   image_ids:list<int>
     * }>
     */
    public function all(): array
    {
        $value = \get_option($this->optionKey(), []);

        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = isset($item['id']) ? (int) $item['id'] : 0;

            if ($id <= 0) {
                continue;
            }

            $items[] = [
                'id' => $id,
                'name' => isset($item['name']) ? (string) $item['name'] : '',
                'description' => isset($item['description']) ? (string) $item['description'] : '',
                'template' => isset($item['template']) ? (string) $item['template'] : 'basic-grid',
                'grid_columns_override' => $this->normalizeOverrideInt($item['grid_columns_override'] ?? null, 2, 6),
                'lightbox_override' => $this->normalizeOverrideBool($item['lightbox_override'] ?? null),
                'hover_zoom_override' => $this->normalizeOverrideBool($item['hover_zoom_override'] ?? null),
                'full_width_override' => $this->normalizeOverrideBool($item['full_width_override'] ?? null),
                'transition_override' => $this->normalizeTransitionOverride($item['transition_override'] ?? null),
                'created_at' => isset($item['created_at']) ? (string) $item['created_at'] : '',
                'image_ids' => $this->normalizeImageIds($item['image_ids'] ?? []),
            ];
        }

        return $items;
    }

    /**
     * @param list<array{
     *   id:int,
     *   name:string,
     *   description:string,
     *   template:string,
     *   grid_columns_override:int|null,
     *   lightbox_override:bool|null,
     *   hover_zoom_override:bool|null,
     *   full_width_override:bool|null,
     *   transition_override:string|null,
     *   created_at:string,
     *   image_ids:list<int>
     * }> $items
     */
    public function replaceAll(array $items): void
    {
        \update_option($this->optionKey(), array_values($items), false);
    }

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   description:string,
     *   template:string,
     *   grid_columns_override:int|null,
     *   lightbox_override:bool|null,
     *   hover_zoom_override:bool|null,
     *   full_width_override:bool|null,
     *   transition_override:string|null,
     *   created_at:string,
     *   image_ids:list<int>
     * }
     */
    public function create(
        string $name,
        string $description = '',
        string $template = 'basic-grid',
        ?int $gridColumnsOverride = null,
        ?bool $lightboxOverride = null,
        ?bool $hoverZoomOverride = null,
        ?bool $fullWidthOverride = null,
        ?string $transitionOverride = null
    ): array
    {
        $items = $this->all();
        $nextId = 1;

        foreach ($items as $item) {
            if ($item['id'] >= $nextId) {
                $nextId = $item['id'] + 1;
            }
        }

        $gallery = [
            'id' => $nextId,
            'name' => $name,
            'description' => $description,
            'template' => $template,
            'grid_columns_override' => $this->normalizeOverrideInt($gridColumnsOverride, 2, 6),
            'lightbox_override' => $this->normalizeOverrideBool($lightboxOverride),
            'hover_zoom_override' => $this->normalizeOverrideBool($hoverZoomOverride),
            'full_width_override' => $this->normalizeOverrideBool($fullWidthOverride),
            'transition_override' => $this->normalizeTransitionOverride($transitionOverride),
            'created_at' => \gmdate('c'),
            'image_ids' => [],
        ];
        $items[] = $gallery;
        $this->replaceAll($items);

        return $gallery;
    }

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   description:string,
     *   template:string,
     *   grid_columns_override:int|null,
     *   lightbox_override:bool|null,
     *   hover_zoom_override:bool|null,
     *   full_width_override:bool|null,
     *   transition_override:string|null,
     *   created_at:string,
     *   image_ids:list<int>
     * }|null
     */
    public function rename(
        int $id,
        string $name,
        string $description,
        ?string $template = null,
        ?int $gridColumnsOverride = null,
        ?bool $lightboxOverride = null,
        ?bool $hoverZoomOverride = null,
        ?bool $fullWidthOverride = null,
        ?string $transitionOverride = null
    ): ?array
    {
        $items = $this->all();
        $updated = null;

        foreach ($items as $index => $item) {
            if ($item['id'] !== $id) {
                continue;
            }

            $items[$index]['name'] = $name;
            $items[$index]['description'] = $description;
            if (is_string($template) && $template !== '') {
                $items[$index]['template'] = $template;
            }
            if ($gridColumnsOverride !== null || array_key_exists('grid_columns_override', $items[$index])) {
                $items[$index]['grid_columns_override'] = $this->normalizeOverrideInt($gridColumnsOverride, 2, 6);
            }
            if ($lightboxOverride !== null || array_key_exists('lightbox_override', $items[$index])) {
                $items[$index]['lightbox_override'] = $this->normalizeOverrideBool($lightboxOverride);
            }
            if ($hoverZoomOverride !== null || array_key_exists('hover_zoom_override', $items[$index])) {
                $items[$index]['hover_zoom_override'] = $this->normalizeOverrideBool($hoverZoomOverride);
            }
            if ($fullWidthOverride !== null || array_key_exists('full_width_override', $items[$index])) {
                $items[$index]['full_width_override'] = $this->normalizeOverrideBool($fullWidthOverride);
            }
            if ($transitionOverride !== null || array_key_exists('transition_override', $items[$index])) {
                $items[$index]['transition_override'] = $this->normalizeTransitionOverride($transitionOverride);
            }
            $updated = $items[$index];
            break;
        }

        if ($updated === null) {
            return null;
        }

        $this->replaceAll($items);

        return $updated;
    }

    public function delete(int $id): bool
    {
        $items = $this->all();
        $remaining = [];
        $deleted = false;

        foreach ($items as $item) {
            if ($item['id'] === $id) {
                $deleted = true;
                continue;
            }

            $remaining[] = $item;
        }

        if (! $deleted) {
            return false;
        }

        $this->replaceAll($remaining);

        return true;
    }

    /**
     * @return list<int>
     */
    public function galleryIdsForImage(int $imageId): array
    {
        $ids = [];

        foreach ($this->all() as $item) {
            if (in_array($imageId, $item['image_ids'], true)) {
                $ids[] = (int) $item['id'];
            }
        }

        return $ids;
    }

    /**
     * @param list<int> $galleryIds
     *
     * @return list<int>
     */
    public function setImageGalleries(int $imageId, array $galleryIds): array
    {
        $items = $this->all();
        $normalizedGalleryIds = array_values(array_unique(array_filter($galleryIds, static fn (int $id): bool => $id > 0)));
        $updatedGalleryIds = [];

        foreach ($items as $index => $item) {
            $current = array_values(array_filter(
                $item['image_ids'],
                static fn (int $id): bool => $id !== $imageId
            ));

            if (in_array((int) $item['id'], $normalizedGalleryIds, true)) {
                $current[] = $imageId;
                $updatedGalleryIds[] = (int) $item['id'];
            }

            $items[$index]['image_ids'] = array_values(array_unique($current));
        }

        $this->replaceAll($items);

        return $updatedGalleryIds;
    }

    /**
     * @param list<int> $imageIds
     */
    public function addImagesToGallery(int $galleryId, array $imageIds): bool
    {
        $items = $this->all();
        $normalizedImageIds = $this->normalizeImageIds($imageIds);
        $updated = false;

        foreach ($items as $index => $item) {
            if ((int) $item['id'] !== $galleryId) {
                continue;
            }

            $items[$index]['image_ids'] = array_values(
                array_unique(
                    array_merge($item['image_ids'], $normalizedImageIds)
                )
            );
            $updated = true;
            break;
        }

        if (! $updated) {
            return false;
        }

        $this->replaceAll($items);

        return true;
    }

    /**
     * @param list<int> $imageIds
     */
    public function setGalleryImages(int $galleryId, array $imageIds): bool
    {
        $items = $this->all();
        $normalizedImageIds = $this->normalizeImageIds($imageIds);
        $updated = false;

        foreach ($items as $index => $item) {
            if ((int) $item['id'] !== $galleryId) {
                continue;
            }

            $items[$index]['image_ids'] = $normalizedImageIds;
            $updated = true;
            break;
        }

        if (! $updated) {
            return false;
        }

        $this->replaceAll($items);

        return true;
    }

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   description:string,
     *   template:string,
     *   grid_columns_override:int|null,
     *   lightbox_override:bool|null,
     *   hover_zoom_override:bool|null,
     *   full_width_override:bool|null,
     *   transition_override:string|null,
     *   created_at:string,
     *   image_ids:list<int>
     * }|null
     */
    public function find(int $id): ?array
    {
        foreach ($this->all() as $item) {
            if ((int) $item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param mixed $value
     *
     * @return list<int>
     */
    private function normalizeImageIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $id) {
            $normalized = (int) $id;

            if ($normalized <= 0) {
                continue;
            }

            $ids[] = $normalized;
        }

        return array_values(array_unique($ids));
    }

    private function normalizeOverrideBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if ($normalized === 'inherit') {
                return null;
            }

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return (bool) $value;
    }

    private function normalizeOverrideInt(mixed $value, int $min, int $max): ?int
    {
        if ($value === null || $value === '' || $value === 'inherit') {
            return null;
        }

        $number = (int) $value;

        if ($number < $min) {
            return $min;
        }

        if ($number > $max) {
            return $max;
        }

        return $number;
    }

    private function normalizeTransitionOverride(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'inherit') {
            return null;
        }

        $normalized = strtolower(trim((string) $value));
        $allowed = ['none', 'slide', 'fade', 'explode', 'implode'];

        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        return null;
    }
}
