<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Services;

/**
 * Tracks explicitly selected media attachments from the admin media picker.
 */
final class MediaManagerTrackSelectionService
{
    public function __construct(private TrackUploadedImageService $trackService)
    {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{
     *     requested_count:int,
     *     tracked_count:int,
     *     skipped_count:int,
     *     tracked_ids:list<int>,
     *     skipped_ids:list<int>
     * }
     */
    public function trackSelection(array $payload): array
    {
        $attachmentIds = $this->normalizeAttachmentIds($payload['attachment_ids'] ?? []);
        $trackedIds = [];
        $skippedIds = [];

        foreach ($attachmentIds as $attachmentId) {
            if ($this->trackService->track($attachmentId)) {
                $trackedIds[] = $attachmentId;
                continue;
            }

            $skippedIds[] = $attachmentId;
        }

        return [
            'requested_count' => count($attachmentIds),
            'tracked_count' => count($trackedIds),
            'skipped_count' => count($skippedIds),
            'tracked_ids' => $trackedIds,
            'skipped_ids' => $skippedIds,
        ];
    }

    /**
     * @param mixed $rawIds
     *
     * @return list<int>
     */
    private function normalizeAttachmentIds(mixed $rawIds): array
    {
        if (is_string($rawIds)) {
            $rawIds = explode(',', $rawIds);
        }

        if (! is_array($rawIds)) {
            return [];
        }

        $normalized = [];

        foreach ($rawIds as $rawId) {
            if (! is_int($rawId) && ! is_string($rawId)) {
                continue;
            }

            $attachmentId = (int) $rawId;

            if ($attachmentId <= 0) {
                continue;
            }

            $normalized[$attachmentId] = $attachmentId;
        }

        return array_values($normalized);
    }
}
