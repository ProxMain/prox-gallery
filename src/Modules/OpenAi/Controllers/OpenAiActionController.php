<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\OpenAi\Controllers;

use Prox\ProxGallery\Contracts\AdminConfigContributorInterface;
use Prox\ProxGallery\Controllers\AbstractActionController;
use Prox\ProxGallery\Modules\OpenAi\OpenAiModule;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiSettingsService;
use Prox\ProxGallery\Modules\OpenAi\Services\OpenAiStoryService;

/**
 * Handles OpenAI admin actions.
 */
final class OpenAiActionController extends AbstractActionController implements AdminConfigContributorInterface
{
    private const ACTION_SETTINGS_GET = 'prox_gallery_openai_settings_get';
    private const ACTION_SETTINGS_UPDATE = 'prox_gallery_openai_settings_update';
    private const ACTION_CONFIG_GET = 'prox_gallery_openai_config_get';
    private const ACTION_GENERATE = 'prox_gallery_openai_generate_story';
    private const ACTION_APPLY = 'prox_gallery_openai_apply_story';

    public function __construct(
        private OpenAiSettingsService $settings,
        private OpenAiStoryService $stories
    ) {
    }

    public function id(): string
    {
        return 'openai.actions';
    }

    /**
     * @return array<string, array{callback:string, nonce_action?:string, capability?:string}>
     */
    protected function actions(): array
    {
        return [
            self::ACTION_SETTINGS_GET => [
                'callback' => 'getSettings',
                'nonce_action' => self::ACTION_SETTINGS_GET,
                'capability' => 'manage_options',
            ],
            self::ACTION_SETTINGS_UPDATE => [
                'callback' => 'updateSettings',
                'nonce_action' => self::ACTION_SETTINGS_UPDATE,
                'capability' => 'manage_options',
            ],
            self::ACTION_CONFIG_GET => [
                'callback' => 'getGenerationConfig',
                'nonce_action' => self::ACTION_CONFIG_GET,
                'capability' => OpenAiModule::CAPABILITY_USE,
            ],
            self::ACTION_GENERATE => [
                'callback' => 'generateStory',
                'nonce_action' => self::ACTION_GENERATE,
                'capability' => OpenAiModule::CAPABILITY_USE,
            ],
            self::ACTION_APPLY => [
                'callback' => 'applyStory',
                'nonce_action' => self::ACTION_APPLY,
                'capability' => OpenAiModule::CAPABILITY_USE,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function getSettings(array $payload, string $action): array
    {
        return [
            'action' => $action,
            'settings' => $this->settings->settings(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateSettings(array $payload, string $action): array
    {
        $normalized = $payload;

        if (isset($payload['languages_csv'])) {
            $normalized['languages'] = (string) $payload['languages_csv'];
        }

        if (isset($payload['prompt_templates_json'])) {
            $decoded = \json_decode((string) $payload['prompt_templates_json'], true);

            if (is_array($decoded)) {
                $normalized['prompt_templates'] = $decoded;
            }
        }

        return [
            'action' => $action,
            'settings' => $this->settings->update($normalized),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function getGenerationConfig(array $payload, string $action): array
    {
        return [
            'action' => $action,
            'config' => $this->settings->generationConfig(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function generateStory(array $payload, string $action): array
    {
        return [
            'action' => $action,
            ...$this->stories->generate($payload),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function applyStory(array $payload, string $action): array
    {
        return [
            'action' => $action,
            ...$this->stories->apply($payload),
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function extendAdminConfig(array $config): array
    {
        $controllers = [];

        if (isset($config['action_controllers']) && is_array($config['action_controllers'])) {
            $controllers = $config['action_controllers'];
        }

        $controllers['openai'] = [
            'settings_get' => [
                'action' => self::ACTION_SETTINGS_GET,
                'nonce' => \wp_create_nonce(self::ACTION_SETTINGS_GET),
            ],
            'settings_update' => [
                'action' => self::ACTION_SETTINGS_UPDATE,
                'nonce' => \wp_create_nonce(self::ACTION_SETTINGS_UPDATE),
            ],
            'config_get' => [
                'action' => self::ACTION_CONFIG_GET,
                'nonce' => \wp_create_nonce(self::ACTION_CONFIG_GET),
            ],
            'generate' => [
                'action' => self::ACTION_GENERATE,
                'nonce' => \wp_create_nonce(self::ACTION_GENERATE),
            ],
            'apply' => [
                'action' => self::ACTION_APPLY,
                'nonce' => \wp_create_nonce(self::ACTION_APPLY),
            ],
        ];

        $config['action_controllers'] = $controllers;

        return $config;
    }
}
