<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Services;

use Prox\ProxGallery\Services\Contracts\FrontendGalleryTemplateRendererInterface;

/**
 * Renders built-in frontend gallery templates.
 */
final class FrontendGalleryTemplateRenderer implements FrontendGalleryTemplateRendererInterface
{
    public function __construct(private TemplateCustomizationService $templateSettings)
    {
    }

    /**
     * @param list<array<string, mixed>> $galleries
     * @param array<string, mixed> $attributes
     */
    public function renderBasicGridTemplate(array $galleries, array $attributes, string $templateSlug): string
    {
        $settings = $this->templateSettings->settings();
        $defaults = [
            'columns' => isset($settings['basic_grid_columns']) ? (int) $settings['basic_grid_columns'] : 4,
            'lightbox' => isset($settings['basic_grid_lightbox']) ? (bool) $settings['basic_grid_lightbox'] : true,
            'hover_zoom' => isset($settings['basic_grid_hover_zoom']) ? (bool) $settings['basic_grid_hover_zoom'] : true,
            'full_width' => isset($settings['basic_grid_full_width']) ? (bool) $settings['basic_grid_full_width'] : false,
            'transition' => isset($settings['basic_grid_transition']) ? (string) $settings['basic_grid_transition'] : 'none',
        ];

        return $this->renderTemplate($galleries, $templateSlug, $defaults, true);
    }

    /**
     * @param list<array<string, mixed>> $galleries
     * @param array<string, mixed> $attributes
     */
    public function renderMasonryTemplate(array $galleries, array $attributes, string $templateSlug): string
    {
        $settings = $this->templateSettings->settings();
        $defaults = [
            'columns' => isset($settings['masonry_columns']) ? (int) $settings['masonry_columns'] : 4,
            'lightbox' => isset($settings['masonry_lightbox']) ? (bool) $settings['masonry_lightbox'] : true,
            'hover_zoom' => isset($settings['masonry_hover_zoom']) ? (bool) $settings['masonry_hover_zoom'] : true,
            'full_width' => isset($settings['masonry_full_width']) ? (bool) $settings['masonry_full_width'] : false,
            'transition' => isset($settings['masonry_transition']) ? (string) $settings['masonry_transition'] : 'none',
        ];

        return $this->renderTemplate($galleries, $templateSlug, $defaults, false);
    }

    /**
     * @param list<array<string, mixed>> $galleries
     * @param array{columns:int, lightbox:bool, hover_zoom:bool, full_width:bool, transition:string} $defaults
     */
    private function renderTemplate(array $galleries, string $templateSlug, array $defaults, bool $rootHasColumns): string
    {
        $columns = $defaults['columns'];
        $lightbox = $defaults['lightbox'];
        $hoverZoom = $defaults['hover_zoom'];
        $fullWidth = $defaults['full_width'];
        $transition = $defaults['transition'];

        if (count($galleries) === 1) {
            $lightbox = $this->resolveBoolOverride($galleries[0]['lightbox_override'] ?? null, $lightbox);
            $hoverZoom = $this->resolveBoolOverride($galleries[0]['hover_zoom_override'] ?? null, $hoverZoom);
            $fullWidth = $this->resolveBoolOverride($galleries[0]['full_width_override'] ?? null, $fullWidth);
        }

        $rootClasses = 'prox-gallery prox-gallery--template-' . \esc_attr($templateSlug);

        if ($lightbox) {
            $rootClasses .= ' prox-gallery--lightbox-enabled';
        }

        if ($hoverZoom) {
            $rootClasses .= ' prox-gallery--hover-zoom';
        }

        if ($fullWidth) {
            $rootClasses .= ' prox-gallery--full-width';
        }

        if ($rootHasColumns) {
            $html = sprintf(
                '<div class="%s" style="--prox-gallery-columns:%d;">',
                $rootClasses,
                max(2, min(6, $columns))
            );
        } else {
            $html = sprintf('<div class="%s">', $rootClasses);
        }

        foreach ($galleries as $gallery) {
            $name = isset($gallery['name']) ? (string) $gallery['name'] : '';
            $description = isset($gallery['description']) ? (string) $gallery['description'] : '';
            $showTitle = $this->resolveBoolOverride($gallery['show_title'] ?? null, true);
            $showDescription = $this->resolveBoolOverride($gallery['show_description'] ?? null, true);
            $resolvedColumns = $this->resolveGridColumns($gallery, $columns);
            $lightboxEnabled = $this->resolveBoolOverride($gallery['lightbox_override'] ?? null, $lightbox);
            $hoverZoomEnabled = $this->resolveBoolOverride($gallery['hover_zoom_override'] ?? null, $hoverZoom);
            $transitionMode = $this->resolveTransitionOverride($gallery['transition_override'] ?? null, $transition);
            $imageIds = isset($gallery['image_ids']) && is_array($gallery['image_ids'])
                ? array_values(array_map(static fn ($id): int => (int) $id, $gallery['image_ids']))
                : [];

            $groupClass = 'prox-gallery__group';
            $groupClass .= $lightboxEnabled ? ' prox-gallery--lightbox-enabled' : '';
            $groupClass .= $hoverZoomEnabled ? ' prox-gallery--hover-zoom' : '';

            $html .= sprintf(
                '<section class="%s" data-prox-gallery-id="%d">',
                \esc_attr($groupClass),
                (int) ($gallery['id'] ?? 0)
            );

            if ($showTitle && $name !== '') {
                $html .= '<h3 class="prox-gallery__title">' . \esc_html($name) . '</h3>';
            }

            if ($showDescription && $description !== '') {
                $html .= '<p class="prox-gallery__description">' . \esc_html($description) . '</p>';
            }

            $html .= sprintf(
                '<div class="prox-gallery__grid" style="--prox-gallery-columns:%d;">',
                max(2, min(6, $resolvedColumns))
            );

            foreach ($imageIds as $imageId) {
                if ($imageId <= 0) {
                    continue;
                }

                $imageUrl = (string) \wp_get_attachment_image_url($imageId, 'large');
                $imageHtml = \wp_get_attachment_image(
                    $imageId,
                    'large',
                    false,
                    [
                        'class' => 'prox-gallery__image',
                        'loading' => 'lazy',
                    ]
                );

                if (! is_string($imageHtml) || $imageHtml === '') {
                    continue;
                }

                $html .= '<figure class="prox-gallery__item">';

                if ($lightboxEnabled && $imageUrl !== '') {
                    $title = isset($gallery['name']) ? (string) $gallery['name'] : '';
                    $html .= sprintf(
                        '<a class="prox-gallery__link" href="%s" data-prox-gallery-lightbox="1" data-prox-gallery-caption="%s" data-prox-gallery-transition="%s" data-prox-gallery-id="%d" data-prox-image-id="%d">',
                        \esc_url($imageUrl),
                        \esc_attr($title),
                        \esc_attr($transitionMode),
                        (int) ($gallery['id'] ?? 0),
                        $imageId
                    );
                    $html .= $imageHtml;
                    $html .= '</a>';
                } else {
                    $html .= $imageHtml;
                }

                $html .= '</figure>';
            }

            $html .= '</div>';
            $html .= '</section>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @param array<string, mixed> $gallery
     */
    private function resolveGridColumns(array $gallery, int $fallback): int
    {
        if (array_key_exists('grid_columns_override', $gallery) && $gallery['grid_columns_override'] !== null) {
            $override = (int) $gallery['grid_columns_override'];

            return max(2, min(6, $override));
        }

        return max(2, min(6, $fallback));
    }

    private function resolveBoolOverride(mixed $value, bool $fallback): bool
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return (bool) $value;
    }

    private function resolveTransitionOverride(mixed $value, string $fallback): string
    {
        $allowed = ['none', 'slide', 'fade', 'explode', 'implode'];

        if (! is_string($value) || $value === '') {
            return in_array($fallback, $allowed, true) ? $fallback : 'none';
        }

        $normalized = strtolower(trim($value));

        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        return in_array($fallback, $allowed, true) ? $fallback : 'none';
    }
}
