<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Controllers;

use Prox\ProxGallery\Controllers\AbstractCliController;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\TrackUploadedImageService;

/**
 * WP-CLI controller for media-library tracking.
 */
final class MediaLibraryCliController extends AbstractCliController
{
    public function __construct(
        private UploadedImageQueueModel $queue,
        private TrackUploadedImageService $service
    )
    {
    }

    protected static function moduleCommand(): string
    {
        return 'media';
    }

    protected static function moduleDescription(): string
    {
        return 'Commands for Prox Media Library tracking.';
    }

    public function register(): void
    {
        $this->registerSubcommand(
            'list-tracked',
            [$this, 'listTrackedImages'],
            [
                'shortdesc' => 'Lists images tracked by the Prox Gallery media module.',
            ]
        );
        $this->registerSubcommand(
            'track',
            [$this, 'trackImage'],
            [
                'shortdesc' => 'Tracks an existing image attachment by ID.',
                'synopsis' => [
                    [
                        'type' => 'positional',
                        'name' => 'id',
                        'description' => 'Attachment ID to track.',
                        'optional' => false,
                    ],
                ],
            ]
        );
        $this->registerSubcommand(
            'validate',
            [$this, 'validateTracked'],
            [
                'shortdesc' => 'Removes tracked rows that no longer point to existing attachments.',
            ]
        );
    }

    /**
     * @param list<string>         $args
     * @param array<string, mixed> $assocArgs
     */
    public function listTrackedImages(array $args = [], array $assocArgs = []): void
    {
        $rows = $this->rows();

        if ($rows === []) {
            \WP_CLI::success('No tracked images found.');

            return;
        }

        \WP_CLI\Utils\format_items(
            'table',
            $rows,
            [
                'id',
                'title',
                'mime_type',
                'width',
                'height',
                'file_size',
                'camera',
                'iso',
                'uploaded_at',
                'uploaded_by',
                'url',
            ]
        );
    }

    /**
     * @param list<string>         $args
     * @param array<string, mixed> $assocArgs
     */
    public function trackImage(array $args = [], array $assocArgs = []): void
    {
        $attachmentId = isset($args[0]) ? (int) $args[0] : 0;

        if ($attachmentId <= 0) {
            \WP_CLI::error('Please provide a valid attachment ID.');
        }

        if (! $this->trackAttachment($attachmentId)) {
            \WP_CLI::error('Attachment was not tracked. Make sure it exists and is an image.');
        }

        \WP_CLI::success(sprintf('Tracked image attachment ID %d.', $attachmentId));
    }

    /**
     * @param list<string>         $args
     * @param array<string, mixed> $assocArgs
     */
    public function validateTracked(array $args = [], array $assocArgs = []): void
    {
        $result = $this->validateTrackedImages();

        \WP_CLI::success(
            sprintf(
                'Validation complete. Removed %d stale tracked rows. Remaining %d.',
                $result['removed'],
                $result['remaining']
            )
        );
    }

    public function trackAttachment(int $attachmentId): bool
    {
        return $this->service->track($attachmentId);
    }

    /**
     * @return array{removed:int, remaining:int}
     */
    public function validateTrackedImages(): array
    {
        $tracked = $this->queue->all();
        $valid = [];

        foreach ($tracked as $image) {
            $post = \get_post($image->id);

            if (! $post instanceof \WP_Post || $post->post_type !== 'attachment') {
                continue;
            }

            $valid[] = $image;
        }

        $removed = count($tracked) - count($valid);
        $this->queue->replaceAll($valid);

        return [
            'removed' => $removed,
            'remaining' => count($valid),
        ];
    }

    /**
     * @return list<array{
     *     id:int,
     *     title:string,
     *     mime_type:string,
     *     uploaded_at:string,
     *     uploaded_by:string,
     *     url:string,
     *     width:int|null,
     *     height:int|null,
     *     file_size:int|null,
     *     file:string,
     *     created_timestamp:int|null,
     *     camera:string,
     *     aperture:string,
     *     focal_length:string,
     *     iso:string,
     *     shutter_speed:string
     * }>
     */
    public function rows(): array
    {
        $rows = [];

        foreach ($this->queue->all() as $image) {
            $rows[] = $image->toArray();
        }

        return $rows;
    }
}
