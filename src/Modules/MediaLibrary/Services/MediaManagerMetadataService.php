<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * Handles tracked attachment metadata updates.
 */
final class MediaManagerMetadataService
{
    public function __construct(private TrackUploadedImageService $trackService)
    {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{
     *     attachment_id:int,
     *     item:array{
     *         id:int,
     *         title:string,
     *         alt_text:string,
     *         caption:string,
     *         description:string
     *     }
     * }
     */
    public function update(array $payload): array
    {
        $attachmentId = isset($payload['attachment_id']) ? (int) $payload['attachment_id'] : 0;

        if ($attachmentId <= 0) {
            throw new InvalidArgumentException('Attachment ID is required.');
        }

        $post = \get_post($attachmentId);

        if (! $post instanceof \WP_Post || $post->post_type !== 'attachment') {
            throw new InvalidArgumentException('Attachment not found.');
        }

        $title = isset($payload['title']) ? \sanitize_text_field((string) $payload['title']) : (string) $post->post_title;
        $altText = isset($payload['alt_text']) ? \sanitize_text_field((string) $payload['alt_text']) : (string) \get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
        $caption = isset($payload['caption']) ? \sanitize_text_field((string) $payload['caption']) : (string) $post->post_excerpt;
        $description = isset($payload['description']) ? \sanitize_textarea_field((string) $payload['description']) : (string) $post->post_content;

        $result = \wp_update_post(
            [
                'ID' => $attachmentId,
                'post_title' => $title,
                'post_excerpt' => $caption,
                'post_content' => $description,
            ],
            true
        );

        if ($result instanceof \WP_Error) {
            throw new RuntimeException($result->get_error_message());
        }

        \update_post_meta($attachmentId, '_wp_attachment_image_alt', $altText);
        $this->trackService->track($attachmentId);

        $updated = \get_post($attachmentId);

        return [
            'attachment_id' => $attachmentId,
            'item' => [
                'id' => $attachmentId,
                'title' => $updated instanceof \WP_Post ? (string) $updated->post_title : $title,
                'alt_text' => (string) \get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
                'caption' => $updated instanceof \WP_Post ? (string) $updated->post_excerpt : $caption,
                'description' => $updated instanceof \WP_Post ? (string) $updated->post_content : $description,
            ],
        ];
    }
}
