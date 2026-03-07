<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers;

use Prox\ProxGallery\Contracts\AdminConfigContributorInterface;
use Prox\ProxGallery\Services\TrackingSummaryService;

/**
 * Admin AJAX controller for analytics/tracking summaries.
 */
final class TrackingActionController extends AbstractActionController implements AdminConfigContributorInterface
{
    private const ACTION_GET = 'prox_gallery_tracking_summary_get';

    public function __construct(
        private TrackingSummaryService $summaryService
    ) {
    }

    public function id(): string
    {
        return 'tracking.actions';
    }

    /**
     * @return array<string, array{callback:string, nonce_action?:string, capability?:string}>
     */
    protected function actions(): array
    {
        return [
            self::ACTION_GET => [
                'callback' => 'getSummary',
                'nonce_action' => self::ACTION_GET,
                'capability' => 'manage_options',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function getSummary(array $payload, string $action): array
    {
        return [
            'action' => $action,
            'summary' => $this->summaryService->summary(),
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

        $controllers['tracking'] = [
            'get' => [
                'action' => self::ACTION_GET,
                'nonce' => \wp_create_nonce(self::ACTION_GET),
            ],
        ];

        $config['action_controllers'] = $controllers;

        return $config;
    }
}
