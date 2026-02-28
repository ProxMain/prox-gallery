<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers;

use Prox\ProxGallery\Services\TemplateCustomizationService;

/**
 * Handles template customization settings AJAX actions.
 */
final class TemplateSettingsActionController extends AbstractActionController
{
    private const ACTION_GET = 'prox_gallery_template_settings_get';
    private const ACTION_UPDATE = 'prox_gallery_template_settings_update';

    public function __construct(private TemplateCustomizationService $service)
    {
    }

    public function id(): string
    {
        return 'template_settings.actions';
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
            self::ACTION_GET => [
                'callback' => 'getSettings',
                'nonce_action' => '',
                'capability' => 'manage_options',
            ],
            self::ACTION_UPDATE => [
                'callback' => 'updateSettings',
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
    public function getSettings(array $payload, string $action): array
    {
        return [
            'action' => $action,
            'settings' => $this->service->settings(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function updateSettings(array $payload, string $action): array
    {
        return [
            'action' => $action,
            'settings' => $this->service->update($payload),
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

        $controllers['template_settings'] = [
            'get' => [
                'action' => self::ACTION_GET,
                'nonce' => \wp_create_nonce(self::ACTION_GET),
            ],
            'update' => [
                'action' => self::ACTION_UPDATE,
                'nonce' => \wp_create_nonce(self::ACTION_UPDATE),
            ],
        ];

        $config['action_controllers'] = $controllers;

        return $config;
    }
}
