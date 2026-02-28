<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Services;

use Prox\ProxGallery\Contracts\ServiceInterface;
use Prox\ProxGallery\Models\GalleryModel;
use Prox\ProxGallery\Policies\FrontendVisibilityPolicy;
use Prox\ProxGallery\States\FrontendGalleryState;

/**
 * Frontend gallery service.
 */
final class FrontendGalleryService implements ServiceInterface
{
    public function __construct(
        private FrontendGalleryState $state,
        private FrontendVisibilityPolicy $policy,
        private GalleryModel $model,
        private TemplateCustomizationService $templateSettings
    ) {
    }

    public function id(): string
    {
        return 'frontend.gallery';
    }

    public function boot(): void
    {
        /**
         * Fires after the frontend gallery service boots.
         *
         * @param FrontendGalleryState     $state  Frontend state instance.
         * @param FrontendVisibilityPolicy $policy Visibility policy instance.
         * @param GalleryModel             $model  Gallery model instance.
         */
        \do_action(
            'prox_gallery/service/frontend_gallery/booted',
            $this->state,
            $this->policy,
            $this->model
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function renderShortcode(array $attributes = []): string
    {
        if (! (bool) \apply_filters('prox_gallery/frontend/can_render', true)) {
            return '';
        }

        $galleryId = isset($attributes['id']) ? (int) $attributes['id'] : 0;
        $requestedTemplate = isset($attributes['template']) ? (string) $attributes['template'] : '';
        $galleries = $this->loadGalleries($galleryId);

        if ($galleries === []) {
            return '';
        }

        if ($requestedTemplate === '' && $galleryId > 0 && count($galleries) === 1) {
            $galleryTemplate = isset($galleries[0]['template']) ? (string) $galleries[0]['template'] : '';
            if ($galleryTemplate !== '') {
                $requestedTemplate = $galleryTemplate;
            }
        }

        $templates = $this->templates();
        $templateSlug = $this->resolveTemplateSlug($requestedTemplate, $templates);
        $template = $templates[$templateSlug] ?? [];
        $callback = $template['render_callback'] ?? null;

        if (! is_callable($callback)) {
            return '';
        }

        $html = (string) call_user_func($callback, $galleries, $attributes, $templateSlug, $this);

        return (string) \apply_filters(
            'prox_gallery/frontend/rendered_html',
            $html,
            $galleries,
            $templateSlug,
            $attributes,
            $this
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function templates(): array
    {
        $templates = [
            'basic-grid' => [
                'label' => 'Basic Grid',
                'is_pro' => false,
                'render_callback' => [$this, 'renderBasicGridTemplate'],
            ],
            'masonry' => [
                'label' => 'Masonry',
                'is_pro' => false,
                'render_callback' => [$this, 'renderMasonryTemplate'],
            ],
        ];

        $filtered = \apply_filters('prox_gallery/frontend/templates', $templates, $this);

        return is_array($filtered) ? $filtered : $templates;
    }

    /**
     * @return list<array{slug:string, label:string, is_pro:bool, available:bool}>
     */
    public function templateCatalog(): array
    {
        $catalog = [];

        foreach ($this->templates() as $slug => $template) {
            if (! is_string($slug) || $slug === '' || ! is_array($template)) {
                continue;
            }

            $catalog[] = [
                'slug' => $slug,
                'label' => isset($template['label']) ? (string) $template['label'] : $slug,
                'is_pro' => $this->templateIsPro($template),
                'available' => $this->templateIsAvailable($slug, $template),
            ];
        }

        $filtered = \apply_filters('prox_gallery/frontend/template_catalog', $catalog, $this);

        return is_array($filtered) ? $filtered : $catalog;
    }

    /**
     * @param list<array<string, mixed>> $galleries
     * @param array<string, mixed> $attributes
     */
    public function renderBasicGridTemplate(array $galleries, array $attributes, string $templateSlug): string
    {
        $settings = $this->templateSettings->settings();
        $columns = isset($settings['basic_grid_columns']) ? (int) $settings['basic_grid_columns'] : 4;
        $lightbox = isset($settings['basic_grid_lightbox']) ? (bool) $settings['basic_grid_lightbox'] : true;
        $hoverZoom = isset($settings['basic_grid_hover_zoom']) ? (bool) $settings['basic_grid_hover_zoom'] : true;
        $fullWidth = isset($settings['basic_grid_full_width']) ? (bool) $settings['basic_grid_full_width'] : false;
        if (count($galleries) === 1) {
            $fullWidth = $this->resolveBoolOverride($galleries[0]['full_width_override'] ?? null, $fullWidth);
        }
        $transition = isset($settings['basic_grid_transition']) ? (string) $settings['basic_grid_transition'] : 'none';

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

        $html = sprintf(
            '<div class="%s" style="--prox-gallery-columns:%d;">',
            $rootClasses,
            max(2, min(6, $columns))
        );

        foreach ($galleries as $gallery) {
            $name = isset($gallery['name']) ? (string) $gallery['name'] : '';
            $description = isset($gallery['description']) ? (string) $gallery['description'] : '';
            $columns = $this->resolveGridColumns($gallery, $columns);
            $lightboxEnabled = $this->resolveBoolOverride($gallery['lightbox_override'] ?? null, $lightbox);
            $hoverZoomEnabled = $this->resolveBoolOverride($gallery['hover_zoom_override'] ?? null, $hoverZoom);
            $transitionMode = $this->resolveTransitionOverride($gallery['transition_override'] ?? null, $transition);
            $imageIds = isset($gallery['image_ids']) && is_array($gallery['image_ids'])
                ? array_values(array_map(static fn ($id): int => (int) $id, $gallery['image_ids']))
                : [];

            $groupClass = 'prox-gallery__group';
            $groupClass .= $lightboxEnabled ? ' prox-gallery--lightbox-enabled' : '';
            $groupClass .= $hoverZoomEnabled ? ' prox-gallery--hover-zoom' : '';
            $html .= sprintf('<section class="%s">', \esc_attr($groupClass));
            $html .= '<h3 class="prox-gallery__title">' . \esc_html($name) . '</h3>';

            if ($description !== '') {
                $html .= '<p class="prox-gallery__description">' . \esc_html($description) . '</p>';
            }

            $html .= sprintf(
                '<div class="prox-gallery__grid" style="--prox-gallery-columns:%d;">',
                max(2, min(6, $columns))
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
                        '<a class="prox-gallery__link" href="%s" data-prox-gallery-lightbox="1" data-prox-gallery-caption="%s" data-prox-gallery-transition="%s">',
                        \esc_url($imageUrl),
                        \esc_attr($title),
                        \esc_attr($transitionMode)
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
     * @param list<array<string, mixed>> $galleries
     * @param array<string, mixed> $attributes
     */
    public function renderMasonryTemplate(array $galleries, array $attributes, string $templateSlug): string
    {
        $settings = $this->templateSettings->settings();
        $columns = isset($settings['masonry_columns']) ? (int) $settings['masonry_columns'] : 4;
        $lightbox = isset($settings['masonry_lightbox']) ? (bool) $settings['masonry_lightbox'] : true;
        $hoverZoom = isset($settings['masonry_hover_zoom']) ? (bool) $settings['masonry_hover_zoom'] : true;
        $fullWidth = isset($settings['masonry_full_width']) ? (bool) $settings['masonry_full_width'] : false;
        if (count($galleries) === 1) {
            $fullWidth = $this->resolveBoolOverride($galleries[0]['full_width_override'] ?? null, $fullWidth);
        }
        $transition = isset($settings['masonry_transition']) ? (string) $settings['masonry_transition'] : 'none';

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

        $html = sprintf('<div class="%s">', $rootClasses);

        foreach ($galleries as $gallery) {
            $name = isset($gallery['name']) ? (string) $gallery['name'] : '';
            $description = isset($gallery['description']) ? (string) $gallery['description'] : '';
            $columns = $this->resolveGridColumns($gallery, $columns);
            $lightboxEnabled = $this->resolveBoolOverride($gallery['lightbox_override'] ?? null, $lightbox);
            $hoverZoomEnabled = $this->resolveBoolOverride($gallery['hover_zoom_override'] ?? null, $hoverZoom);
            $transitionMode = $this->resolveTransitionOverride($gallery['transition_override'] ?? null, $transition);
            $imageIds = isset($gallery['image_ids']) && is_array($gallery['image_ids'])
                ? array_values(array_map(static fn ($id): int => (int) $id, $gallery['image_ids']))
                : [];

            $groupClass = 'prox-gallery__group';
            $groupClass .= $lightboxEnabled ? ' prox-gallery--lightbox-enabled' : '';
            $groupClass .= $hoverZoomEnabled ? ' prox-gallery--hover-zoom' : '';
            $html .= sprintf('<section class="%s">', \esc_attr($groupClass));
            $html .= '<h3 class="prox-gallery__title">' . \esc_html($name) . '</h3>';

            if ($description !== '') {
                $html .= '<p class="prox-gallery__description">' . \esc_html($description) . '</p>';
            }

            $html .= sprintf(
                '<div class="prox-gallery__grid" style="--prox-gallery-columns:%d;">',
                max(2, min(6, $columns))
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
                        '<a class="prox-gallery__link" href="%s" data-prox-gallery-lightbox="1" data-prox-gallery-caption="%s" data-prox-gallery-transition="%s">',
                        \esc_url($imageUrl),
                        \esc_attr($title),
                        \esc_attr($transitionMode)
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
     * @param array<string, array<string, mixed>> $templates
     */
    private function resolveTemplateSlug(string $requestedTemplate, array $templates): string
    {
        if (
            $requestedTemplate !== ''
            && isset($templates[$requestedTemplate])
            && is_array($templates[$requestedTemplate])
            && $this->templateIsAvailable($requestedTemplate, $templates[$requestedTemplate])
        ) {
            return $requestedTemplate;
        }

        $keys = array_keys($templates);

        foreach ($keys as $key) {
            if (
                is_string($key)
                && $key !== ''
                && isset($templates[$key])
                && is_array($templates[$key])
                && $this->templateIsAvailable($key, $templates[$key])
            ) {
                return $key;
            }
        }

        return 'basic-grid';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadGalleries(int $galleryId): array
    {
        $value = \get_option('prox_gallery_galleries', []);

        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = isset($item['id']) ? (int) $item['id'] : 0;

            if ($id <= 0) {
                continue;
            }

            if ($galleryId > 0 && $id !== $galleryId) {
                continue;
            }

            $imageIds = isset($item['image_ids']) && is_array($item['image_ids'])
                ? array_values(
                    array_filter(
                        array_map(static fn ($imageId): int => (int) $imageId, $item['image_ids']),
                        static fn (int $imageId): bool => $imageId > 0
                    )
                )
                : [];

            $items[] = [
                'id' => $id,
                'name' => isset($item['name']) ? (string) $item['name'] : '',
                'description' => isset($item['description']) ? (string) $item['description'] : '',
                'template' => isset($item['template']) ? (string) $item['template'] : 'basic-grid',
                'grid_columns_override' => isset($item['grid_columns_override']) ? (int) $item['grid_columns_override'] : null,
                'lightbox_override' => array_key_exists('lightbox_override', $item) && is_bool($item['lightbox_override'])
                    ? $item['lightbox_override']
                    : null,
                'hover_zoom_override' => array_key_exists('hover_zoom_override', $item) && is_bool($item['hover_zoom_override'])
                    ? $item['hover_zoom_override']
                    : null,
                'full_width_override' => array_key_exists('full_width_override', $item) && is_bool($item['full_width_override'])
                    ? $item['full_width_override']
                    : null,
                'transition_override' => array_key_exists('transition_override', $item) && is_string($item['transition_override'])
                    ? $item['transition_override']
                    : null,
                'image_ids' => $imageIds,
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $template
     */
    private function templateIsPro(array $template): bool
    {
        return isset($template['is_pro']) && (bool) $template['is_pro'];
    }

    /**
     * @param array<string, mixed> $template
     */
    private function templateIsAvailable(string $slug, array $template): bool
    {
        $default = ! $this->templateIsPro($template);
        $allowed = \apply_filters(
            'prox_gallery/frontend/template_is_available',
            $default,
            $slug,
            $template,
            $this
        );

        return (bool) $allowed;
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
