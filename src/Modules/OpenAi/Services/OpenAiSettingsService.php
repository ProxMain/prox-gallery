<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Modules\OpenAi\Services;

use InvalidArgumentException;
use Prox\ProxGallery\States\AdminConfigurationState;

/**
 * Stores and validates OpenAI settings.
 */
final class OpenAiSettingsService
{
    public const DEFAULT_MODEL = 'gpt-4.1-mini';

    public function __construct(private AdminConfigurationState $state)
    {
    }

    /**
     * @return array{
     *   api_key:string,
     *   model:string,
     *   languages:list<string>,
     *   prompt_templates:list<array{key:string,label:string,prompt:string,built_in:bool}>
     * }
     */
    public function settings(): array
    {
        $raw = \get_option($this->state->optionKey(), []);

        if (! is_array($raw)) {
            $raw = [];
        }

        $openAi = isset($raw['openai']) && is_array($raw['openai']) ? $raw['openai'] : [];
        $apiKey = isset($openAi['api_key']) ? trim((string) $openAi['api_key']) : '';
        $model = isset($openAi['model']) ? trim((string) $openAi['model']) : self::DEFAULT_MODEL;

        if ($model === '') {
            $model = self::DEFAULT_MODEL;
        }

        $languages = $this->normalizeLanguages($openAi['languages'] ?? []);
        $templates = $this->normalizeTemplates($openAi['prompt_templates'] ?? []);

        return [
            'api_key' => $apiKey,
            'model' => $model,
            'languages' => $languages,
            'prompt_templates' => $templates,
        ];
    }

    /**
     * @return array{
     *   model:string,
     *   languages:list<string>,
     *   prompt_templates:list<array{key:string,label:string,prompt:string,built_in:bool}>
     * }
     */
    public function generationConfig(): array
    {
        $settings = $this->settings();

        return [
            'model' => $settings['model'],
            'languages' => $settings['languages'],
            'prompt_templates' => $settings['prompt_templates'],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{
     *   api_key:string,
     *   model:string,
     *   languages:list<string>,
     *   prompt_templates:list<array{key:string,label:string,prompt:string,built_in:bool}>
     * }
     */
    public function update(array $payload): array
    {
        $current = $this->settings();
        $next = $current;

        if (array_key_exists('api_key', $payload)) {
            $next['api_key'] = trim((string) $payload['api_key']);
        }

        if (array_key_exists('model', $payload)) {
            $model = trim((string) $payload['model']);
            $next['model'] = $model === '' ? self::DEFAULT_MODEL : $model;
        }

        if (array_key_exists('languages', $payload)) {
            $next['languages'] = $this->normalizeLanguages($payload['languages']);
        }

        if (array_key_exists('prompt_templates', $payload)) {
            $next['prompt_templates'] = $this->normalizeTemplates($payload['prompt_templates']);
        }

        if ($next['api_key'] === '') {
            throw new InvalidArgumentException('OpenAI API key is required.');
        }

        $options = \get_option($this->state->optionKey(), []);

        if (! is_array($options)) {
            $options = [];
        }

        $options['openai'] = [
            'api_key' => $next['api_key'],
            'model' => $next['model'],
            'languages' => $next['languages'],
            'prompt_templates' => $next['prompt_templates'],
        ];

        \update_option($this->state->optionKey(), $options, false);

        return $next;
    }

    /**
     * @param mixed $value
     *
     * @return list<string>
     */
    private function normalizeLanguages(mixed $value): array
    {
        $defaults = ['English', 'German', 'Dutch', 'French'];

        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        if (! is_array($value)) {
            return $defaults;
        }

        $languages = [];

        foreach ($value as $item) {
            $name = trim((string) $item);

            if ($name === '') {
                continue;
            }

            $languages[] = $name;
        }

        $languages = array_values(array_unique($languages));

        return $languages === [] ? $defaults : $languages;
    }

    /**
     * @param mixed $value
     *
     * @return list<array{key:string,label:string,prompt:string,built_in:bool}>
     */
    private function normalizeTemplates(mixed $value): array
    {
        $builtIns = [
            [
                'key' => 'factual',
                'label' => 'Factual',
                'prompt' => 'Describe the image in a factual and objective way. Focus on visible elements and avoid speculation.',
                'built_in' => true,
            ],
            [
                'key' => 'technical',
                'label' => 'Technical',
                'prompt' => 'Describe the image with technical detail: composition, lighting, perspective, color behavior, and photographic characteristics.',
                'built_in' => true,
            ],
            [
                'key' => 'creative',
                'label' => 'Creative',
                'prompt' => 'Write a creative short story inspired by the image while staying faithful to what is visible.',
                'built_in' => true,
            ],
        ];

        $inputRows = [];

        if (is_array($value)) {
            $inputRows = $value;
        }

        $customRows = [];

        foreach ($inputRows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $key = \sanitize_key((string) ($row['key'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            $prompt = trim((string) ($row['prompt'] ?? ''));
            $builtIn = isset($row['built_in']) && (bool) $row['built_in'];

            if ($key === '' || $label === '' || $prompt === '') {
                continue;
            }

            if (in_array($key, ['factual', 'technical', 'creative'], true)) {
                continue;
            }

            $customRows[] = [
                'key' => $key,
                'label' => $label,
                'prompt' => $prompt,
                'built_in' => $builtIn,
            ];
        }

        $normalized = [];

        foreach ($builtIns as $builtInRow) {
            $match = null;

            foreach ($inputRows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $key = \sanitize_key((string) ($row['key'] ?? ''));

                if ($key !== $builtInRow['key']) {
                    continue;
                }

                $label = trim((string) ($row['label'] ?? ''));
                $prompt = trim((string) ($row['prompt'] ?? ''));

                if ($label === '' || $prompt === '') {
                    continue;
                }

                $match = [
                    'key' => $builtInRow['key'],
                    'label' => $label,
                    'prompt' => $prompt,
                    'built_in' => true,
                ];

                break;
            }

            $normalized[] = $match ?? $builtInRow;
        }

        foreach ($customRows as $row) {
            $normalized[] = [
                'key' => $row['key'],
                'label' => $row['label'],
                'prompt' => $row['prompt'],
                'built_in' => false,
            ];
        }

        return $normalized;
    }
}
