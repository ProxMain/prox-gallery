<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Controllers;

use Prox\ProxGallery\Contracts\ControllerInterface;
use Prox\ProxGallery\Modules\MediaLibrary\Services\TrackUploadedImageService;

/**
 * Hooks into WordPress media upload events.
 */
final class MediaUploadController implements ControllerInterface
{
    private const TRACK_EVENT = 'prox_gallery/module/media_library/deferred_track';

    public function __construct(private TrackUploadedImageService $service)
    {
    }

    public function id(): string
    {
        return 'media_library.upload_controller';
    }

    public function boot(): void
    {
        \add_action('add_attachment', [$this, 'onAttachmentCreated']);
        \add_filter('wp_generate_attachment_metadata', [$this, 'onMetadataGenerated'], 10, 2);
        \add_action(self::TRACK_EVENT, [$this, 'handleDeferredTrack'], 10, 1);
    }

    public function onAttachmentCreated(int $attachmentId): void
    {
        $this->scheduleTrack($attachmentId);
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    public function onMetadataGenerated(array $metadata, int $attachmentId): array
    {
        $this->scheduleTrack($attachmentId);

        return $metadata;
    }

    public function handleDeferredTrack(int $attachmentId): void
    {
        $this->service->track($attachmentId);
    }

    /**
     * Debounces tracking writes by scheduling one delayed event per attachment.
     */
    private function scheduleTrack(int $attachmentId): void
    {
        if ($attachmentId <= 0) {
            return;
        }

        if (\wp_next_scheduled(self::TRACK_EVENT, [$attachmentId])) {
            return;
        }

        \wp_schedule_single_event(\time() + 10, self::TRACK_EVENT, [$attachmentId]);
    }
}
