<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\MediaLibrary\Controllers;

use InvalidArgumentException;
use Prox\ProxGallery\Controllers\AbstractActionController;
use Prox\ProxGallery\Modules\MediaLibrary\Models\UploadedImageQueueModel;
use Prox\ProxGallery\Modules\MediaLibrary\Services\MediaCategoryService;

/**
 * Handles AJAX actions for media category suggestions and assignments.
 */
final class MediaCategoryActionController extends AbstractActionController
{
    private const ACTION_SUGGEST = 'prox_gallery_media_category_suggest';
    private const ACTION_LIST = 'prox_gallery_media_category_list';
    private const ACTION_ASSIGN = 'prox_gallery_media_category_assign';

    public function __construct(
        private MediaCategoryService $service,
        private UploadedImageQueueModel $queue
    )
    {
    }

    public function id(): string
    {
        return 'media_category.actions';
    }

    public function boot(): void
    {
        parent::boot();

        \add_filter('prox_gallery/admin/config_payload', [$this, 'extendAdminConfig']);
    }

    /**
     * @return array<string, array{callback:string, nonce_action?:string, capability?:string}>
     */
    protected function actions(): array
    {
        return [
            self::ACTION_SUGGEST => [
                'callback' => 'suggestCategories',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
            self::ACTION_LIST => [
                'callback' => 'listCategoriesForAttachment',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
            self::ACTION_ASSIGN => [
                'callback' => 'assignCategoriesToAttachment',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function suggestCategories(array $payload, string $action): array
    {
        $query = isset($payload['query']) ? (string) $payload['query'] : '';
        $limit = isset($payload['limit']) ? (int) $payload['limit'] : 10;
        $terms = $this->withTrackedCounts($this->service->suggest($query, $limit));

        return [
            'action' => $action,
            'query' => $query,
            'items' => $terms,
            'count' => count($terms),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function listCategoriesForAttachment(array $payload, string $action): array
    {
        $attachmentId = isset($payload['attachment_id']) ? (int) $payload['attachment_id'] : 0;

        if ($attachmentId <= 0) {
            throw new InvalidArgumentException('Attachment ID is required.');
        }

        $terms = $this->withTrackedCounts($this->service->listForAttachment($attachmentId));

        return [
            'action' => $action,
            'attachment_id' => $attachmentId,
            'items' => $terms,
            'count' => count($terms),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function assignCategoriesToAttachment(array $payload, string $action): array
    {
        $attachmentId = isset($payload['attachment_id']) ? (int) $payload['attachment_id'] : 0;

        if ($attachmentId <= 0) {
            throw new InvalidArgumentException('Attachment ID is required.');
        }

        $categories = $this->categoriesFromPayload($payload['categories'] ?? []);
        $assigned = $this->withTrackedCounts($this->service->assignToAttachment($attachmentId, $categories));

        return [
            'action' => $action,
            'attachment_id' => $attachmentId,
            'items' => $assigned,
            'count' => count($assigned),
        ];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public function extendAdminConfig(array $config): array
    {
        $controllers = [];

        if (isset($config['action_controllers']) && is_array($config['action_controllers'])) {
            $controllers = $config['action_controllers'];
        }

        $controllers['media_category'] = [
            'suggest' => [
                'action' => self::ACTION_SUGGEST,
                'nonce' => \wp_create_nonce(self::ACTION_SUGGEST),
            ],
            'list' => [
                'action' => self::ACTION_LIST,
                'nonce' => \wp_create_nonce(self::ACTION_LIST),
            ],
            'assign' => [
                'action' => self::ACTION_ASSIGN,
                'nonce' => \wp_create_nonce(self::ACTION_ASSIGN),
            ],
            'taxonomy' => $this->service->taxonomy(),
        ];

        $config['action_controllers'] = $controllers;

        return $config;
    }

    /**
     * @param mixed $value
     *
     * @return list<string>
     */
    private function categoriesFromPayload(mixed $value): array
    {
        if (is_string($value)) {
            $parts = array_map('trim', explode(',', $value));
            return array_values(array_filter($parts, static fn (string $item): bool => $item !== ''));
        }

        if (! is_array($value)) {
            return [];
        }

        $categories = [];

        foreach ($value as $category) {
            if (! is_scalar($category)) {
                continue;
            }

            $categories[] = (string) $category;
        }

        return $categories;
    }

    /**
     * @param list<array{id:int, name:string, slug:string, count:int}> $items
     *
     * @return list<array{id:int, name:string, slug:string, count:int}>
     */
    private function withTrackedCounts(array $items): array
    {
        $trackedIds = array_map(
            static fn ($image): int => (int) $image->id,
            $this->queue->all()
        );
        $trackedLookup = array_fill_keys($trackedIds, true);

        if ($trackedLookup === []) {
            return array_map(
                static fn (array $item): array => [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'slug' => $item['slug'],
                    'count' => 0,
                ],
                $items
            );
        }

        $rows = [];

        foreach ($items as $item) {
            $objectIds = \get_objects_in_term((int) $item['id'], $this->service->taxonomy());
            $count = 0;

            if (is_array($objectIds)) {
                foreach ($objectIds as $objectId) {
                    $id = (int) $objectId;

                    if (isset($trackedLookup[$id])) {
                        $count++;
                    }
                }
            }

            $rows[] = [
                'id' => (int) $item['id'],
                'name' => (string) $item['name'],
                'slug' => (string) $item['slug'],
                'count' => $count,
            ];
        }

        return $rows;
    }
}
