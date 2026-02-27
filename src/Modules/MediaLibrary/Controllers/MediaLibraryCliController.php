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

    public function trackAttachment(int $attachmentId): bool
    {
        return $this->service->track($attachmentId);
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
