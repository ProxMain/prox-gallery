<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Controllers;

use Prox\ProxGallery\Contracts\AdminConfigContributorInterface;
use Prox\ProxGallery\Controllers\AbstractActionController;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerListService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerMetadataService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerSyncService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaManagerTrackSelectionService;
use Prox\ProxGallery\Modules\MediaLibrary\Services\TrackUploadedImageService;
use Prox\ProxGallery\Policies\AdminCapabilityPolicy;

/**
 * Handles secured Media Manager AJAX actions.
 */
final class MediaManagerActionController extends AbstractActionController implements AdminConfigContributorInterface
{
    private const ACTION_LIST = 'prox_gallery_media_manager_list';
    private const ACTION_SYNC = 'prox_gallery_media_manager_sync';
    private const ACTION_TRACK_SELECTED = 'prox_gallery_media_manager_track_selected';
    private const ACTION_UPDATE = 'prox_gallery_media_manager_update';

    private MediaManagerListService $listService;
    private MediaManagerSyncService $syncService;
    private MediaManagerTrackSelectionService $trackSelectionService;
    private MediaManagerMetadataService $metadataService;

    public function __construct(
        private UploadedImageQueueModel $queue,
        private TrackUploadedImageService $trackService,
        MediaManagerListService $listService,
        MediaManagerSyncService $syncService,
        MediaManagerTrackSelectionService $trackSelectionService,
        MediaManagerMetadataService $metadataService
    ) {
        $this->listService = $listService;
        $this->syncService = $syncService;
        $this->trackSelectionService = $trackSelectionService;
        $this->metadataService = $metadataService;
    }

    public function id(): string
    {
        return 'media_manager.actions';
    }

    /**
     * @return array<string, array{callback:string, nonce_action?:string, capability?:string}>
     */
    protected function actions(): array
    {
        return [
            self::ACTION_LIST => [
                'callback' => 'listTrackedImages',
                'nonce_action' => self::ACTION_LIST,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
            ],
            self::ACTION_SYNC => [
                'callback' => 'syncOverview',
                'nonce_action' => self::ACTION_SYNC,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
            ],
            self::ACTION_UPDATE => [
                'callback' => 'updateTrackedImageMetadata',
                'nonce_action' => self::ACTION_UPDATE,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
            ],
            self::ACTION_TRACK_SELECTED => [
                'callback' => 'trackSelectedAttachments',
                'nonce_action' => self::ACTION_TRACK_SELECTED,
                'capability' => AdminCapabilityPolicy::CAPABILITY_MANAGE,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function listTrackedImages(array $payload, string $action): array
    {
        $items = $this->listService->listItems($payload);

        return [
            'action' => $action,
            'items' => $items,
            'count' => count($items),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function syncOverview(array $payload, string $action): array
    {
        $result = $this->syncService->sync($payload);

        return [
            'action' => $action,
            ...$result,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function updateTrackedImageMetadata(array $payload, string $action): array
    {
        $result = $this->metadataService->update($payload);

        return [
            'action' => $action,
            ...$result,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function trackSelectedAttachments(array $payload, string $action): array
    {
        $result = $this->trackSelectionService->trackSelection($payload);

        return [
            'action' => $action,
            ...$result,
        ];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public function extendAdminConfig(array $config): array
    {
        return $this->extendAdminActionConfig(
            $config,
            'media_manager',
            [
                'list' => self::ACTION_LIST,
                'sync' => self::ACTION_SYNC,
                'track_selected' => self::ACTION_TRACK_SELECTED,
                'update' => self::ACTION_UPDATE,
            ]
        );
    }
}
