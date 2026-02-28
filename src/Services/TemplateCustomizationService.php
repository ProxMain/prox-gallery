<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Services;

use Prox\ProxGallery\Contracts\ServiceInterface;
use Prox\ProxGallery\States\AdminConfigurationState;

/**
 * Manages frontend template customization settings.
 */
final class TemplateCustomizationService implements ServiceInterface
{
    public function __construct(private AdminConfigurationState $state)
    {
    }

    public function id(): string
    {
        return 'template.customization';
    }

    public function boot(): void
    {
        /**
         * Fires after template customization settings service boots.
         */
        \do_action('prox_gallery/service/template_customization/booted', $this);
    }

    /**
     * @return array{
     *   basic_grid_columns:int,
     *   basic_grid_lightbox:bool,
     *   basic_grid_hover_zoom:bool,
     *   basic_grid_full_width:bool,
     *   basic_grid_transition:string,
     *   masonry_columns:int,
     *   masonry_lightbox:bool,
     *   masonry_hover_zoom:bool,
     *   masonry_full_width:bool,
     *   masonry_transition:string
     * }
     */
    public function settings(): array
    {
        $raw = \get_option($this->state->optionKey(), []);

        if (! is_array($raw)) {
            $raw = [];
        }

        return [
            'basic_grid_columns' => $this->normalizeColumns($raw['basic_grid_columns'] ?? 4),
            'basic_grid_lightbox' => $this->normalizeBool($raw['basic_grid_lightbox'] ?? true),
            'basic_grid_hover_zoom' => $this->normalizeBool($raw['basic_grid_hover_zoom'] ?? true),
            'basic_grid_full_width' => $this->normalizeBool($raw['basic_grid_full_width'] ?? false),
            'basic_grid_transition' => $this->normalizeTransition($raw['basic_grid_transition'] ?? 'none'),
            'masonry_columns' => $this->normalizeColumns($raw['masonry_columns'] ?? 4),
            'masonry_lightbox' => $this->normalizeBool($raw['masonry_lightbox'] ?? true),
            'masonry_hover_zoom' => $this->normalizeBool($raw['masonry_hover_zoom'] ?? true),
            'masonry_full_width' => $this->normalizeBool($raw['masonry_full_width'] ?? false),
            'masonry_transition' => $this->normalizeTransition($raw['masonry_transition'] ?? 'none'),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{
     *   basic_grid_columns:int,
     *   basic_grid_lightbox:bool,
     *   basic_grid_hover_zoom:bool,
     *   basic_grid_full_width:bool,
     *   basic_grid_transition:string,
     *   masonry_columns:int,
     *   masonry_lightbox:bool,
     *   masonry_hover_zoom:bool,
     *   masonry_full_width:bool,
     *   masonry_transition:string
     * }
     */
    public function update(array $payload): array
    {
        $current = $this->settings();
        $next = $current;

        if (array_key_exists('basic_grid_columns', $payload)) {
            $next['basic_grid_columns'] = $this->normalizeColumns($payload['basic_grid_columns']);
        }

        if (array_key_exists('basic_grid_lightbox', $payload)) {
            $next['basic_grid_lightbox'] = $this->normalizeBool($payload['basic_grid_lightbox']);
        }

        if (array_key_exists('basic_grid_hover_zoom', $payload)) {
            $next['basic_grid_hover_zoom'] = $this->normalizeBool($payload['basic_grid_hover_zoom']);
        }

        if (array_key_exists('basic_grid_full_width', $payload)) {
            $next['basic_grid_full_width'] = $this->normalizeBool($payload['basic_grid_full_width']);
        }

        if (array_key_exists('basic_grid_transition', $payload)) {
            $next['basic_grid_transition'] = $this->normalizeTransition($payload['basic_grid_transition']);
        }

        if (array_key_exists('masonry_columns', $payload)) {
            $next['masonry_columns'] = $this->normalizeColumns($payload['masonry_columns']);
        }

        if (array_key_exists('masonry_lightbox', $payload)) {
            $next['masonry_lightbox'] = $this->normalizeBool($payload['masonry_lightbox']);
        }

        if (array_key_exists('masonry_hover_zoom', $payload)) {
            $next['masonry_hover_zoom'] = $this->normalizeBool($payload['masonry_hover_zoom']);
        }

        if (array_key_exists('masonry_full_width', $payload)) {
            $next['masonry_full_width'] = $this->normalizeBool($payload['masonry_full_width']);
        }

        if (array_key_exists('masonry_transition', $payload)) {
            $next['masonry_transition'] = $this->normalizeTransition($payload['masonry_transition']);
        }

        \update_option($this->state->optionKey(), $next, false);

        return $next;
    }

    private function normalizeColumns(mixed $value): int
    {
        $columns = (int) $value;

        if ($columns < 2) {
            return 2;
        }

        if ($columns > 6) {
            return 6;
        }

        return $columns;
    }

    private function normalizeBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    private function normalizeTransition(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $allowed = ['none', 'slide', 'fade', 'explode', 'implode'];

        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        return 'none';
    }
}
