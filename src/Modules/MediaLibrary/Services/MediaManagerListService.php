<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Services;

use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;

/**
 * Builds admin list payloads for tracked media items.
 */
final class MediaManagerListService
{
    public function __construct(private UploadedImageQueueModel $queue)
    {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<array<string, mixed>>
     */
    public function listItems(array $payload = []): array
    {
        $items = [];
        $validTrackedImages = [];

        foreach ($this->queue->all() as $image) {
            $post = \get_post($image->id);

            if (! $post instanceof \WP_Post || $post->post_type !== 'attachment' || ! \wp_attachment_is_image($image->id)) {
                continue;
            }

            $validTrackedImages[] = $image;
            $viewUrl = (string) $image->url;
            $editUrl = \function_exists('get_edit_post_link')
                ? (string) (\get_edit_post_link($image->id, '') ?? '')
                : '';
            $deleteUrl = \function_exists('get_delete_post_link')
                ? (string) (\get_delete_post_link($image->id, '', true) ?? '')
                : '';
            $altText = (string) \get_post_meta($image->id, '_wp_attachment_image_alt', true);
            $caption = $post instanceof \WP_Post ? (string) $post->post_excerpt : '';
            $description = $post instanceof \WP_Post ? (string) $post->post_content : '';
            $categories = [];

            if (\taxonomy_exists('prox_media_category')) {
                $terms = \wp_get_object_terms(
                    $image->id,
                    'prox_media_category',
                    [
                        'orderby' => 'name',
                        'order' => 'ASC',
                    ]
                );

                if (is_array($terms)) {
                    foreach ($terms as $term) {
                        if (! $term instanceof \WP_Term) {
                            continue;
                        }

                        $categories[] = [
                            'id' => (int) $term->term_id,
                            'name' => (string) $term->name,
                            'slug' => (string) $term->slug,
                        ];
                    }
                }
            }

            $items[] = [
                'id' => $image->id,
                'title' => $image->title,
                'mime_type' => $image->mimeType,
                'uploaded_at' => $image->uploadedAt,
                'uploaded_by' => $image->uploadedBy,
                'url' => $image->url,
                'view_url' => $viewUrl,
                'edit_url' => $editUrl,
                'delete_url' => $deleteUrl,
                'alt_text' => $altText,
                'caption' => $caption,
                'description' => $description,
                'categories' => $categories,
                'width' => $image->width,
                'height' => $image->height,
                'file_size' => $image->fileSize,
            ];
        }

        if (count($validTrackedImages) !== count($this->queue->all())) {
            $this->queue->replaceAll($validTrackedImages);
        }

        /**
         * Filters tracked media list payload before responding.
         *
         * @param list<array<string, mixed>> $items
         * @param array<string, mixed>        $payload
         */
        return (array) \apply_filters('prox_gallery/module/media_manager/list_payload', $items, $payload);
    }
}
