<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Services;

use Prox\ProxGallery\Contracts\ServiceInterface;
use Prox\ProxGallery\Modules\MediaLibrary\DTO\TrackedImageDto;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;

/**
 * Tracks new image uploads from WordPress media events.
 */
final class TrackUploadedImageService implements ServiceInterface
{
    public function __construct(private UploadedImageQueueModel $queue)
    {
    }

    public function id(): string
    {
        return 'media_library.track_uploaded_image';
    }

    public function boot(): void
    {
        /**
         * Fires after media upload tracking services are booted.
         *
         * @param self $service Service instance.
         */
        \do_action('prox_gallery/module/media_library/service/booted', $this);
    }

    public function track(int $attachmentId): bool
    {
        return $this->trackWithMetadata($attachmentId, null);
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function trackWithMetadata(int $attachmentId, ?array $metadata): bool
    {
        if (! $this->isImageAttachment($attachmentId)) {
            return false;
        }

        $image = TrackedImageDto::fromAttachmentId($attachmentId, $metadata);

        if ($image === null) {
            return false;
        }

        $this->queue->remember($image);

        /**
         * Fires after an uploaded image is stored for gallery overview usage.
         *
         * @param int $attachmentId Attachment post ID.
         */
        \do_action('prox_gallery/module/media_library/image_tracked', $attachmentId);

        return true;
    }

    private function isImageAttachment(int $attachmentId): bool
    {
        if (\wp_attachment_is_image($attachmentId)) {
            return true;
        }

        $mimeType = \get_post_mime_type($attachmentId);

        return is_string($mimeType) && str_starts_with($mimeType, 'image/');
    }
}
